// ============================================================
// SmartServe – Supabase Client-Side Adapter / Interceptor
// File: supabase_helper.js
// Intercepts all local PHP fetch calls and routes them directly
// to Supabase REST API and Storage bucket.
// ============================================================

(function() {
    const SUPABASE_URL = 'https://phheuvsnkllqxjkgoodh.supabase.co';
    const SUPABASE_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InBoaGV1dnNua2xscXhqa2dvb2RoIiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODMyMjgwOTcsImV4cCI6MjA5ODgwNDA5N30.xTG6XBv32ln3Ks-HGE7NDE1wimO9ul4aKcSySH4wb-A';

    // Helper to dynamically load external scripts (like bcryptjs)
    function loadScript(src) {
        return new Promise((resolve, reject) => {
            if (document.querySelector(`script[src="${src}"]`)) {
                return resolve();
            }
            const s = document.createElement('script');
            s.src = src;
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }

    // Helper to perform Supabase queries
    async function supabaseQuery(path, method = 'GET', body = null, extraHeaders = {}) {
        const url = `${SUPABASE_URL}/rest/v1/${path}`;
        const headers = {
            'apikey': SUPABASE_KEY,
            'Authorization': `Bearer ${SUPABASE_KEY}`,
            'Content-Type': 'application/json',
            ...extraHeaders
        };
        const options = {
            method: method,
            headers: headers
        };
        if (body) {
            options.body = JSON.stringify(body);
        }
        const res = await originalFetch(url, options);
        if (!res.ok) {
            const errText = await res.text();
            throw new Error(`Supabase query failed: ${res.status} ${errText}`);
        }
        const text = await res.text();
        return text ? JSON.parse(text) : {};
    }

    // Helper to upload files to Supabase Storage
    async function supabaseUpload(bucket, path, fileBlob) {
        const url = `${SUPABASE_URL}/storage/v1/object/${bucket}/${path}`;
        const headers = {
            'apikey': SUPABASE_KEY,
            'Authorization': `Bearer ${SUPABASE_KEY}`,
            'Content-Type': fileBlob.type || 'application/octet-stream',
            'x-upsert': 'true'
        };
        const res = await originalFetch(url, {
            method: 'POST',
            headers: headers,
            body: fileBlob
        });
        if (!res.ok) {
            const errText = await res.text();
            throw new Error(`Supabase upload failed: ${res.status} ${errText}`);
        }
        return `${SUPABASE_URL}/storage/v1/object/public/${bucket}/${path}`;
    }

    // Wrap JS values to mimic Response objects
    function mockResponse(data, status = 200) {
        return new Response(JSON.stringify(data), {
            status: status,
            headers: { 'Content-Type': 'application/json' }
        });
    }

    // Intercept clicks on links pointing to logout.php
    document.addEventListener('DOMContentLoaded', () => {
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (link && link.getAttribute('href') === 'logout.php') {
                e.preventDefault();
                localStorage.removeItem('user_id');
                localStorage.removeItem('username');
                localStorage.removeItem('role');
                window.location.href = 'index.html';
            }
        });
    });

    // Save the original fetch
    const originalFetch = window.fetch;

    // Overwrite the global fetch
    window.fetch = async function(url, options) {
        let urlStr = typeof url === 'string' ? url : url.url;
        const parsedUrl = new URL(urlStr, window.location.href);
        const path = parsedUrl.pathname.split('/').pop();
        const params = new URLSearchParams(parsedUrl.search);
        const action = params.get('action');

        // Parse Request Body if any
        let body = {};
        if (options && options.body) {
            if (options.body instanceof FormData) {
                for (let [k, v] of options.body.entries()) {
                    body[k] = v;
                }
            } else if (typeof options.body === 'string') {
                try {
                    body = JSON.parse(options.body);
                } catch(e) {
                    body = Object.fromEntries(new URLSearchParams(options.body));
                }
            }
        }

        // ==========================================
        // 1. check_session.php
        // ==========================================
        if (path === 'check_session.php') {
            const userId = localStorage.getItem('user_id');
            const username = localStorage.getItem('username');
            const role = localStorage.getItem('role');
            if (userId && username && role) {
                return mockResponse({ logged_in: true, username, role });
            }
            return mockResponse({ logged_in: false });
        }

        // ==========================================
        // 2. login.php
        // ==========================================
        if (path === 'login.php') {
            const { username, password, role } = body;
            try {
                const users = await supabaseQuery(`users?username=eq.${encodeURIComponent(username)}&role=eq.${encodeURIComponent(role)}&select=*`);
                if (users.length === 0) {
                    return mockResponse({ success: false, message: `No ${role} account found with that username.` });
                }
                const user = users[0];
                let isMatch = false;
                if (password === user.password) {
                    isMatch = true;
                } else if (user.password.startsWith('$2y$') || user.password.startsWith('$2a$')) {
                    if (typeof bcrypt === 'undefined') {
                        await loadScript('https://cdn.jsdelivr.net/npm/bcryptjs@2.4.3/dist/bcrypt.min.js');
                    }
                    const bcryptLib = typeof bcrypt !== 'undefined' ? bcrypt : dcodeIO.bcrypt;
                    // Convert PHP bcrypt prefix $2y$ to $2a$ for JS compatibility
                    const normalizedHash = user.password.replace(/^\$2y\$/, '$2a$');
                    isMatch = bcryptLib.compareSync(password, normalizedHash);
                }

                if (isMatch) {
                    localStorage.setItem('user_id', user.user_id);
                    localStorage.setItem('username', user.username);
                    localStorage.setItem('role', user.role);

                    let redirect = 'index.html';
                    if (user.role === 'admin') redirect = 'admin.html';
                    else if (user.role === 'waiter') redirect = 'waiter.html';
                    else if (user.role === 'kitchen') redirect = 'kitchen.html';

                    return mockResponse({
                        success: true,
                        message: `Login successful! Welcome, ${user.username}.`,
                        role: user.role,
                        redirect: `${redirect}?_cb=${Date.now()}`
                    });
                } else {
                    return mockResponse({ success: false, message: 'Incorrect password. Please try again.' });
                }
            } catch(e) {
                console.error(e);
                return mockResponse({ success: false, message: 'Database connection failed.' }, 500);
            }
        }

        // ==========================================
        // 3. logout.php
        // ==========================================
        if (path === 'logout.php') {
            localStorage.removeItem('user_id');
            localStorage.removeItem('username');
            localStorage.removeItem('role');
            return mockResponse({ success: true });
        }

        // ==========================================
        // 4. menu_api.php
        // ==========================================
        if (path === 'menu_api.php') {
            try {
                if (action === 'fetch') {
                    const data = await supabaseQuery('menu_items?select=item_id,item_name,price,description,image_url,is_available,categories(category_name)&order=item_id.desc');
                    const items = data.map(item => ({
                        id: item.item_id,
                        name: item.item_name,
                        category: item.categories ? item.categories.category_name : null,
                        price: item.price,
                        description: item.description,
                        image_path: item.image_url,
                        is_available: item.is_available ? 1 : 0
                    }));
                    return mockResponse({ success: true, data: items });
                }

                // Protect administrative actions
                const role = localStorage.getItem('role');
                if (role !== 'admin') {
                    return mockResponse({ success: false, message: 'Unauthorized access.' }, 403);
                }

                if (action === 'toggle_availability') {
                    const id = body.id;
                    const status = body.status;
                    await supabaseQuery(`menu_items?item_id=eq.${id}`, 'PATCH', { is_available: parseInt(status) === 1 });
                    return mockResponse({ success: true });
                }

                if (action === 'delete') {
                    const id = body.id;
                    await supabaseQuery(`menu_items?item_id=eq.${id}`, 'DELETE');
                    return mockResponse({ success: true, message: 'Item deleted successfully.' });
                }

                if (action === 'add' || action === 'edit') {
                    const id = body.id;
                    const name = body.name;
                    const category = body.category;
                    const price = body.price;
                    const description = body.description;

                    // Resolve category_id
                    let categoryId = null;
                    const cats = await supabaseQuery(`categories?category_name=eq.${encodeURIComponent(category)}&select=category_id`);
                    if (cats.length > 0) {
                        categoryId = cats[0].category_id;
                    } else {
                        // Create category
                        const newCat = await supabaseQuery('categories', 'POST', { category_name: category }, { 'Prefer': 'return=representation' });
                        categoryId = newCat[0].category_id;
                    }

                    // Handle image upload if a file was selected in the FormData
                    let imageUrl = null;
                    if (options && options.body && options.body.get && options.body.get('image')) {
                        const imageFile = options.body.get('image');
                        if (imageFile && imageFile.size > 0) {
                            const ext = imageFile.name.split('.').pop();
                            const filename = `menu_${Date.now()}_${Math.random().toString(36).substr(2, 9)}.${ext}`;
                            imageUrl = await supabaseUpload('uploads', filename, imageFile);
                        }
                    }

                    if (action === 'add') {
                        await supabaseQuery('menu_items', 'POST', {
                            category_id: categoryId,
                            item_name: name,
                            price: price,
                            description: description,
                            image_url: imageUrl,
                            is_available: true
                        });
                        return mockResponse({ success: true, message: 'Item added successfully.' });
                    } else {
                        const updateData = {
                            category_id: categoryId,
                            item_name: name,
                            price: price,
                            description: description
                        };
                        if (imageUrl) {
                            updateData.image_url = imageUrl;
                        }
                        await supabaseQuery(`menu_items?item_id=eq.${id}`, 'PATCH', updateData);
                        return mockResponse({ success: true, message: 'Item updated successfully.' });
                    }
                }
            } catch(e) {
                console.error(e);
                return mockResponse({ success: false, message: e.message }, 500);
            }
        }

        // ==========================================
        // 5. order_api.php
        // ==========================================
        if (path === 'order_api.php') {
            try {
                const tableNumber = parseInt(body.table_number || 0);
                const customerToken = body.customer_token || 'Staff Order';
                const cartItems = body.items || [];

                if (tableNumber <= 0 || cartItems.length === 0) {
                    return mockResponse({ success: false, message: 'Invalid order request.' }, 400);
                }

                // 1. Find or create session
                let sessionId = null;
                const sessions = await supabaseQuery(`table_sessions?table_number=eq.${tableNumber}&status=eq.active&select=session_id`);
                if (sessions.length > 0) {
                    sessionId = sessions[0].session_id;
                } else {
                    const newSession = await supabaseQuery('table_sessions', 'POST', {
                        table_number: tableNumber,
                        status: 'active'
                    }, { 'Prefer': 'return=representation' });
                    sessionId = newSession[0].session_id;
                }

                // 2. Insert order header
                const newOrder = await supabaseQuery('orders', 'POST', {
                    table_number: tableNumber,
                    session_id: sessionId,
                    customer_token: customerToken,
                    total_amount: 0.00,
                    status: 'pending'
                }, { 'Prefer': 'return=representation' });
                const orderId = newOrder[0].order_id;

                // 3. Process items
                let totalAmount = 0;
                for (let item of cartItems) {
                    const itemId = parseInt(item.id);
                    const qty = parseInt(item.qty);

                    const dbItems = await supabaseQuery(`menu_items?item_id=eq.${itemId}&select=price,is_available`);
                    if (dbItems.length === 0 || !dbItems[0].is_available) {
                        throw new Error(`Item ${itemId} is unavailable or does not exist.`);
                    }
                    const price = parseFloat(dbItems[0].price);
                    const subtotal = price * qty;
                    totalAmount += subtotal;

                    await supabaseQuery('order_items', 'POST', {
                        order_id: orderId,
                        item_id: itemId,
                        quantity: qty,
                        subtotal: subtotal
                    });
                }

                // 4. Update order total with 10% tax
                const finalTotal = parseFloat((totalAmount * 1.10).toFixed(2));
                await supabaseQuery(`orders?order_id=eq.${orderId}`, 'PATCH', { total_amount: finalTotal });

                return mockResponse({
                    success: true,
                    message: 'Order placed successfully!',
                    order_id: orderId,
                    session_id: sessionId,
                    total_amount: finalTotal
                });
            } catch(e) {
                console.error(e);
                return mockResponse({ success: false, message: e.message }, 500);
            }
        }

        // ==========================================
        // 6. order_status_api.php
        // ==========================================
        if (path === 'order_status_api.php') {
            try {
                const orderId = parseInt(params.get('order_id') || 0);
                const orders = await supabaseQuery(`orders?order_id=eq.${orderId}&select=*`);
                if (orders.length === 0) {
                    return mockResponse({ success: false, message: 'Order not found.' }, 404);
                }
                const order = orders[0];
                return mockResponse({
                    success: true,
                    order_id: order.order_id,
                    table_number: order.table_number,
                    status: order.status,
                    order_date: order.order_date
                });
            } catch(e) {
                console.error(e);
                return mockResponse({ success: false, message: e.message }, 500);
            }
        }

        // ==========================================
        // 7. session_api.php
        // ==========================================
        if (path === 'session_api.php') {
            try {
                if (action === 'get_or_create') {
                    const tableNumber = parseInt(body.table_number || params.get('table_number') || 0);
                    const sessions = await supabaseQuery(`table_sessions?table_number=eq.${tableNumber}&status=eq.active&select=session_id`);
                    if (sessions.length > 0) {
                        return mockResponse({ success: true, session_id: sessions[0].session_id, is_new: false });
                    }
                    const newSession = await supabaseQuery('table_sessions', 'POST', {
                        table_number: tableNumber,
                        status: 'active'
                    }, { 'Prefer': 'return=representation' });
                    return mockResponse({ success: true, session_id: newSession[0].session_id, is_new: true });
                }

                if (action === 'request_bill') {
                    const orderId = parseInt(body.order_id || 0);
                    await supabaseQuery(`orders?order_id=eq.${orderId}`, 'PATCH', { bill_requested: true });
                    return mockResponse({ success: true, message: 'Bill request sent to waiter!' });
                }

                // Protected administrative actions
                const role = localStorage.getItem('role');
                if (role !== 'waiter' && role !== 'admin') {
                    return mockResponse({ success: false, message: 'Unauthorized.' }, 403);
                }

                if (action === 'get_table_bill') {
                    let sessionId = parseInt(params.get('session_id') || 0);
                    const tableNumber = parseInt(params.get('table_number') || 0);

                    if (sessionId <= 0 && tableNumber > 0) {
                        const sessions = await supabaseQuery(`table_sessions?table_number=eq.${tableNumber}&status=eq.active&select=session_id`);
                        if (sessions.length > 0) {
                            sessionId = sessions[0].session_id;
                        }
                    }

                    if (sessionId <= 0) {
                        return mockResponse({ success: false, message: 'No active session found for this table.' }, 404);
                    }

                    const sessionDetails = await supabaseQuery(`table_sessions?session_id=eq.${sessionId}&select=*`);
                    if (sessionDetails.length === 0) {
                        return mockResponse({ success: false, message: 'Session not found.' }, 404);
                    }

                    // Query orders + order items in session
                    const orders = await supabaseQuery(`orders?session_id=eq.${sessionId}&status=neq.cancelled&select=*,order_items(*,menu_items(*))&order=order_id.asc`);

                    const ordersArr = orders.map(o => ({
                        order_id: o.order_id,
                        customer_token: o.customer_token,
                        total_amount: parseFloat(o.total_amount),
                        status: o.status,
                        order_date: o.order_date,
                        bill_requested: o.bill_requested ? 1 : 0,
                        items: (o.order_items || []).map(oi => ({
                            item_name: oi.menu_items ? oi.menu_items.item_name : 'Item Deleted',
                            quantity: oi.quantity,
                            price: oi.menu_items ? parseFloat(oi.menu_items.price) : 0,
                            subtotal: parseFloat(oi.subtotal)
                        }))
                    }));

                    const grandTotal = ordersArr.reduce((sum, o) => sum + o.total_amount, 0);
                    const billRequested = ordersArr.some(o => o.bill_requested);

                    return mockResponse({
                        success: true,
                        session_id: sessionId,
                        table_number: sessionDetails[0].table_number,
                        opened_at: sessionDetails[0].opened_at,
                        orders: ordersArr,
                        grand_total: grandTotal,
                        order_count: ordersArr.length,
                        bill_requested: billRequested
                    });
                }

                if (action === 'close_session') {
                    const sessionId = parseInt(body.session_id || 0);
                    // Mark non-cancelled orders as served
                    await supabaseQuery(`orders?session_id=eq.${sessionId}&status=not.in.(served,cancelled)`, 'PATCH', { status: 'served' });
                    // Close session
                    await supabaseQuery(`table_sessions?session_id=eq.${sessionId}`, 'PATCH', {
                        status: 'closed',
                        closed_at: new Date().toISOString()
                    });
                    return mockResponse({ success: true, message: 'Table session closed. Table is now free.' });
                }

                if (action === 'get_active_sessions') {
                    const activeSessions = await supabaseQuery('table_sessions?status=eq.active&select=*,orders(*)&order=opened_at.asc');
                    const formatted = activeSessions.map(s => {
                        const orders = (s.orders || []).filter(o => o.status !== 'cancelled');
                        const orderCount = orders.length;
                        const totalAmount = orders.reduce((sum, o) => sum + parseFloat(o.total_amount), 0);
                        const billRequested = orders.some(o => o.bill_requested) ? 1 : 0;

                        // Dominate status computation
                        const statuses = orders.map(o => o.status);
                        let dominant = 'idle';
                        if (statuses.includes('pending')) dominant = 'pending';
                        else if (statuses.includes('preparing')) dominant = 'preparing';
                        else if (statuses.includes('ready')) dominant = 'ready';

                        return {
                            session_id: s.session_id,
                            table_number: s.table_number,
                            opened_at: s.opened_at,
                            order_count: orderCount,
                            total_amount: totalAmount,
                            bill_requested: billRequested,
                            dominant_status: dominant
                        };
                    });
                    return mockResponse({ success: true, data: formatted });
                }
            } catch(e) {
                console.error(e);
                return mockResponse({ success: false, message: e.message }, 500);
            }
        }

        // ==========================================
        // 8. kitchen_api.php
        // ==========================================
        if (path === 'kitchen_api.php') {
            try {
                // Auth check
                const role = localStorage.getItem('role');
                if (role !== 'kitchen' && role !== 'admin') {
                    return mockResponse({ success: false, message: 'Unauthorized access.' }, 403);
                }

                if (action === 'fetch_active') {
                    const orders = await supabaseQuery('orders?status=in.(pending,preparing,ready)&select=*,order_items(*,menu_items(*))&order=order_date.asc');
                    const ordersList = orders.map(o => ({
                        order_id: o.order_id,
                        table_number: o.table_number,
                        session_id: o.session_id,
                        total_amount: parseFloat(o.total_amount),
                        status: o.status,
                        order_date: o.order_date,
                        items: (o.order_items || []).map(oi => ({
                            item_name: oi.menu_items ? oi.menu_items.item_name : 'Item Deleted',
                            quantity: oi.quantity,
                            image_path: oi.menu_items ? oi.menu_items.image_url : null
                        }))
                    }));

                    // Construct table_groups
                    const groups = {};
                    ordersList.forEach(o => {
                        const key = `${o.table_number}_${o.session_id || 'legacy'}`;
                        if (!groups[key]) {
                            groups[key] = {
                                table_number: o.table_number,
                                session_id: o.session_id,
                                orders: []
                            };
                        }
                        groups[key].orders.push(o);
                    });

                    return mockResponse({
                        success: true,
                        data: ordersList,
                        table_groups: Object.values(groups)
                    });
                }

                if (action === 'update_status') {
                    const orderId = parseInt(body.order_id || 0);
                    const status = body.status;
                    await supabaseQuery(`orders?order_id=eq.${orderId}`, 'PATCH', { status: status });
                    return mockResponse({ success: true, message: `Order #${orderId} status updated to ${status} successfully.` });
                }
            } catch(e) {
                console.error(e);
                return mockResponse({ success: false, message: e.message }, 500);
            }
        }

        // ==========================================
        // 9. waiter_api.php
        // ==========================================
        if (path === 'waiter_api.php') {
            try {
                // Auth check
                const role = localStorage.getItem('role');
                if (role !== 'waiter' && role !== 'admin') {
                    return mockResponse({ success: false, message: 'Unauthorized access.' }, 403);
                }

                if (action === 'fetch_orders') {
                    const todayStr = new Date().toISOString().slice(0, 10);
                    const orders = await supabaseQuery(`orders?select=*,order_items(*,menu_items(*))&order=order_date.desc`);
                    
                    // Filter in client (active orders OR placed today)
                    const filtered = orders.filter(o => {
                        const isActive = ['pending', 'preparing', 'ready'].includes(o.status);
                        const isToday = o.order_date.startsWith(todayStr);
                        return isActive || isToday;
                    });

                    const ordersList = filtered.map(o => ({
                        order_id: o.order_id,
                        table_number: o.table_number,
                        session_id: o.session_id,
                        total_amount: parseFloat(o.total_amount),
                        status: o.status,
                        order_date: o.order_date,
                        customer_token: o.customer_token,
                        bill_requested: o.bill_requested ? 1 : 0,
                        items: (o.order_items || []).map(oi => ({
                            item_id: oi.item_id,
                            item_name: oi.menu_items ? oi.menu_items.item_name : 'Item Deleted',
                            quantity: oi.quantity,
                            price: oi.menu_items ? parseFloat(oi.menu_items.price) : 0,
                            subtotal: parseFloat(oi.subtotal),
                            image_path: oi.menu_items ? oi.menu_items.image_url : null
                        }))
                    }));

                    return mockResponse({ success: true, data: ordersList });
                }

                if (action === 'update_status') {
                    const orderId = parseInt(body.order_id || 0);
                    const status = body.status;
                    await supabaseQuery(`orders?order_id=eq.${orderId}`, 'PATCH', { status: status });
                    return mockResponse({ success: true, message: `Order #${orderId} status updated to ${status} successfully.` });
                }
            } catch(e) {
                console.error(e);
                return mockResponse({ success: false, message: e.message }, 500);
            }
        }

        // ==========================================
        // 10. qr_api.php
        // ==========================================
        if (path === 'qr_api.php') {
            try {
                if (action === 'fetch') {
                    const qrs = await supabaseQuery('tables_qr?select=*&order=table_number.asc');
                    return mockResponse({ success: true, data: qrs });
                }

                // Protected actions (admin only)
                const role = localStorage.getItem('role');
                if (role !== 'admin') {
                    return mockResponse({ success: false, message: 'Unauthorized access.' }, 403);
                }

                if (action === 'save') {
                    const tableNum = body.table_number;
                    const qrLink = body.qr_link;
                    const imageData = body.image_data; // base64

                    // Convert base64 to Blob
                    const parts = imageData.split(';base64,');
                    const mime = parts[0].split(':')[1];
                    const raw = window.atob(parts[1]);
                    const rawLength = raw.length;
                    const uInt8Array = new Uint8Array(rawLength);
                    for (let i = 0; i < rawLength; ++i) {
                        uInt8Array[i] = raw.charCodeAt(i);
                    }
                    const blob = new Blob([uInt8Array], { type: mime });

                    // Upload to Storage
                    const filename = `table_${tableNum}.png`;
                    const publicUrl = await supabaseUpload('uploads', `qrcodes/${filename}`, blob);

                    // Check if already exists
                    const existing = await supabaseQuery(`tables_qr?table_number=eq.${tableNum}&select=id`);
                    if (existing.length > 0) {
                        // Update
                        await supabaseQuery(`tables_qr?table_number=eq.${tableNum}`, 'PATCH', {
                            qr_link: qrLink,
                            qr_image_path: publicUrl
                        });
                    } else {
                        // Insert
                        await supabaseQuery('tables_qr', 'POST', {
                            table_number: tableNum,
                            qr_link: qrLink,
                            qr_image_path: publicUrl
                        });
                    }

                    return mockResponse({ success: true, message: 'QR Code saved successfully', image_path: publicUrl });
                }

                if (action === 'delete') {
                    const id = body.id;
                    await supabaseQuery(`tables_qr?id=eq.${id}`, 'DELETE');
                    return mockResponse({ success: true, message: 'QR Code deleted successfully' });
                }
            } catch(e) {
                console.error(e);
                return mockResponse({ success: false, message: e.message }, 500);
            }
        }

        // ==========================================
        // 11. staff_api.php
        // ==========================================
        if (path === 'staff_api.php') {
            try {
                // Auth check
                const role = localStorage.getItem('role');
                if (role !== 'admin') {
                    return mockResponse({ success: false, message: 'Unauthorized access.' }, 403);
                }

                if (action === 'fetch') {
                    const staff = await supabaseQuery('staff?select=*&order=staff_id.desc');
                    return mockResponse({ success: true, data: staff });
                }

                if (action === 'register') {
                    const full_name = body.full_name;
                    const id_number = body.id_number;
                    const phone_number = body.phone_number;
                    const staffRole = body.role;

                    // Validate uniqueness
                    const existStaff = await supabaseQuery(`staff?id_number=eq.${encodeURIComponent(id_number)}&select=staff_id`);
                    if (existStaff.length > 0) {
                        return mockResponse({ success: false, message: 'NIC / ID Number already exists.' });
                    }
                    const existUser = await supabaseQuery(`users?username=eq.${encodeURIComponent(full_name)}&select=user_id`);
                    if (existUser.length > 0) {
                        return mockResponse({ success: false, message: 'User with this name already exists. Please use a unique name.' });
                    }

                    // Hash password using bcryptjs
                    if (typeof bcrypt === 'undefined') {
                        await loadScript('https://cdn.jsdelivr.net/npm/bcryptjs@2.4.3/dist/bcrypt.min.js');
                    }
                    const bcryptLib = typeof bcrypt !== 'undefined' ? bcrypt : dcodeIO.bcrypt;
                    const hashedPassword = bcryptLib.hashSync(id_number, 10);

                    // Insert Staff
                    const newStaff = await supabaseQuery('staff', 'POST', {
                        full_name,
                        id_number,
                        phone_number,
                        role: staffRole
                    }, { 'Prefer': 'return=representation' });
                    const staffId = newStaff[0].staff_id;

                    // Insert User
                    await supabaseQuery('users', 'POST', {
                        username: full_name,
                        password: hashedPassword,
                        role: staffRole,
                        staff_id: staffId
                    });

                    return mockResponse({ success: true, message: 'Staff registered successfully! Username is their Name, Password is the NIC.' });
                }
            } catch(e) {
                console.error(e);
                return mockResponse({ success: false, message: e.message }, 500);
            }
        }

        // ==========================================
        // 12. admin_api.php
        // ==========================================
        if (path === 'admin_api.php') {
            try {
                // Auth check
                const role = localStorage.getItem('role');
                if (role !== 'admin') {
                    return mockResponse({ success: false, message: 'Unauthorized access.' }, 403);
                }

                const todayStart = new Date();
                todayStart.setHours(0,0,0,0);
                const todayStartISO = todayStart.toISOString();

                if (action === 'dashboard_data') {
                    // Fetch orders starting today
                    const orders = await supabaseQuery(`orders?order_date=gte.${todayStartISO}&select=*,order_items(*,menu_items(*))`);
                    
                    const revenue = orders.filter(o => o.status !== 'cancelled')
                                          .reduce((sum, o) => sum + parseFloat(o.total_amount), 0);
                    
                    // Fetch all active orders regardless of date
                    const activeOrdersRes = await supabaseQuery('orders?status=in.(pending,preparing,ready)&select=order_id');
                    const activeOrdersCount = activeOrdersRes.length;

                    // Items served today
                    let itemsServed = 0;
                    orders.filter(o => o.status === 'served').forEach(o => {
                        (o.order_items || []).forEach(oi => {
                            itemsServed += oi.quantity;
                        });
                    });

                    // Live orders list
                    const liveOrdersRes = await supabaseQuery('orders?status=in.(pending,preparing,ready)&select=*&order=order_date.desc');
                    const liveOrdersList = liveOrdersRes.map(o => ({
                        order_id: o.order_id,
                        table_number: o.table_number,
                        total_amount: parseFloat(o.total_amount),
                        status: o.status,
                        order_date: o.order_date
                    }));

                    // Popular categories
                    // Fetch all served order items to compute popularity
                    const allOrdersWithItems = await supabaseQuery('orders?status=neq.cancelled&select=order_id,order_items(quantity,menu_items(categories(category_name)))');
                    const categoryCounts = {};
                    allOrdersWithItems.forEach(o => {
                        (o.order_items || []).forEach(oi => {
                            if (oi.menu_items && oi.menu_items.categories) {
                                const catName = oi.menu_items.categories.category_name;
                                categoryCounts[catName] = (categoryCounts[catName] || 0) + oi.quantity;
                            }
                        });
                    });

                    const popularCategories = Object.entries(categoryCounts)
                        .map(([name, total_qty]) => ({ name, total_qty }))
                        .sort((a, b) => b.total_qty - a.total_qty)
                        .slice(0, 5);

                    return mockResponse({
                        success: true,
                        stats: {
                            revenue: parseFloat(revenue.toFixed(2)),
                            active_orders: activeOrdersCount,
                            items_served: itemsServed
                        },
                        live_orders: liveOrdersList,
                        chart: {
                            labels: popularCategories.map(c => c.name),
                            data: popularCategories.map(c => c.total_qty)
                        }
                    });
                }

                if (action === 'fetch_orders') {
                    const search = (params.get('search') || '').trim().toLowerCase();
                    const statusFilter = (params.get('status') || '').trim();

                    const orders = await supabaseQuery('orders?select=*,order_items(*,menu_items(*))&order=order_date.desc&limit=200');

                    // Calculate KPIs
                    const todayStr = new Date().toISOString().slice(0, 10);
                    let totalToday = 0;
                    let pendingCount = 0;
                    let preparingCount = 0;
                    let completedCount = 0;

                    orders.forEach(o => {
                        if (o.order_date.startsWith(todayStr) && o.status !== 'cancelled') totalToday++;
                        if (o.status === 'pending') pendingCount++;
                        else if (o.status === 'preparing') preparingCount++;
                        else if (['ready', 'served'].includes(o.status)) completedCount++;
                    });

                    // Filter
                    let filtered = orders;
                    if (statusFilter && statusFilter !== 'all') {
                        filtered = filtered.filter(o => o.status === statusFilter);
                    }
                    if (search) {
                        filtered = filtered.filter(o => 
                            String(o.order_id).includes(search) || 
                            String(o.table_number).includes(search) || 
                            (o.customer_token || '').toLowerCase().includes(search)
                        );
                    }

                    const list = filtered.map(o => {
                        const summary = (o.order_items || []).map(oi => 
                            `${oi.quantity}x ${oi.menu_items ? oi.menu_items.item_name : 'Item Deleted'}`
                        ).join(', ');
                        return {
                            order_id: o.order_id,
                            table_number: o.table_number,
                            customer_token: o.customer_token || '—',
                            items_summary: summary || 'No items',
                            total_amount: parseFloat(o.total_amount),
                            status: o.status,
                            order_date: o.order_date
                        };
                    });

                    return mockResponse({
                        success: true,
                        orders: list,
                        kpi: { total_today: totalToday, pending_count: pendingCount, preparing_count: preparingCount, completed_count: completedCount }
                    });
                }

                if (action === 'order_details') {
                    const orderId = parseInt(params.get('order_id') || 0);
                    const orders = await supabaseQuery(`orders?order_id=eq.${orderId}&select=*,order_items(*,menu_items(*))`);
                    if (orders.length === 0) {
                        return mockResponse({ success: false, message: 'Order not found.' }, 404);
                    }
                    const order = orders[0];

                    let subtotalRaw = 0;
                    const itemsList = (order.order_items || []).map(oi => {
                        const price = oi.menu_items ? parseFloat(oi.menu_items.price) : 0;
                        const sub = parseFloat(oi.subtotal);
                        subtotalRaw += sub;
                        return {
                            item_name: oi.menu_items ? oi.menu_items.item_name : 'Item Deleted',
                            unit_price: price,
                            quantity: oi.quantity,
                            subtotal: sub
                        };
                    });

                    const finalTotal = parseFloat(order.total_amount);
                    const tax = parseFloat((finalTotal - subtotalRaw).toFixed(2));

                    return mockResponse({
                        success: true,
                        order_id: order.order_id,
                        table_number: order.table_number,
                        customer_token: order.customer_token || '—',
                        status: order.status,
                        order_date: order.order_date,
                        items: itemsList,
                        subtotal: parseFloat(subtotalRaw.toFixed(2)),
                        tax: tax,
                        total: finalTotal
                    });
                }

                if (action === 'cancel_order') {
                    const orderId = parseInt(body.order_id || 0);
                    const check = await supabaseQuery(`orders?order_id=eq.${orderId}&select=status`);
                    if (check.length === 0) {
                        return mockResponse({ success: false, message: 'Order not found.' }, 404);
                    }
                    if (['served', 'cancelled'].includes(check[0].status)) {
                        return mockResponse({ success: false, message: `Cannot cancel a ${check[0].status} order.` });
                    }
                    await supabaseQuery(`orders?order_id=eq.${orderId}`, 'PATCH', { status: 'cancelled' });
                    return mockResponse({ success: true, message: `Order #${orderId} has been cancelled.` });
                }

                if (action === 'fetch_reports') {
                    const startDate = params.get('start_date');
                    const endDate = params.get('end_date');
                    const startStr = `${startDate}T00:00:00`;
                    const endStr = `${endDate}T23:59:59`;

                    const orders = await supabaseQuery(`orders?order_date=gte.${startStr}&order_date=lte.${endStr}&select=*,order_items(*,menu_items(*,categories(*)))`);
                    
                    const nonCancelled = orders.filter(o => o.status !== 'cancelled');

                    // 1. Revenue Timeline
                    const revenueByDate = {};
                    nonCancelled.forEach(o => {
                        const d = o.order_date.slice(0, 10);
                        revenueByDate[d] = (revenueByDate[d] || 0) + parseFloat(o.total_amount);
                    });
                    const timelineRev = Object.entries(revenueByDate).map(([report_date, revenue]) => ({
                        report_date, revenue
                    })).sort((a,b) => a.report_date.localeCompare(b.report_date));

                    const grossRev = nonCancelled.reduce((sum, o) => sum + parseFloat(o.total_amount), 0);
                    const orderCount = nonCancelled.length;
                    const aov = orderCount > 0 ? grossRev / orderCount : 0;

                    // 2. Volume Timeline
                    const volumeByHour = {};
                    nonCancelled.forEach(o => {
                        const hr = new Date(o.order_date).getHours();
                        volumeByHour[hr] = (volumeByHour[hr] || 0) + 1;
                    });
                    const timelineVol = Object.entries(volumeByHour).map(([hour_of_day, count]) => ({
                        hour_of_day: parseInt(hour_of_day), count
                    })).sort((a,b) => a.hour_of_day - b.hour_of_day);

                    const cancelledOrders = orders.filter(o => o.status === 'cancelled').length;

                    // 3. Menu Item Performance
                    const itemsPerformance = {};
                    const catPerformance = {};
                    nonCancelled.forEach(o => {
                        (o.order_items || []).forEach(oi => {
                            if (oi.menu_items) {
                                const itemName = oi.menu_items.item_name;
                                const catName = oi.menu_items.categories ? oi.menu_items.categories.category_name : 'Uncategorized';
                                const qty = oi.quantity;
                                const sub = parseFloat(oi.subtotal);

                                if (!itemsPerformance[itemName]) {
                                    itemsPerformance[itemName] = { item_name: itemName, category_name: catName, sold_qty: 0, item_revenue: 0 };
                                }
                                itemsPerformance[itemName].sold_qty += qty;
                                itemsPerformance[itemName].item_revenue += sub;

                                catPerformance[catName] = (catPerformance[catName] || 0) + sub;
                            }
                        });
                    });

                    const topItems = Object.values(itemsPerformance)
                        .sort((a, b) => b.sold_qty - a.sold_qty)
                        .slice(0, 10);

                    const catRevenue = Object.entries(catPerformance).map(([category_name, cat_revenue]) => ({
                        category_name, cat_revenue
                    }));

                    // 4. Table stats
                    const tableTraffic = {};
                    nonCancelled.forEach(o => {
                        const t = o.table_number;
                        if (!tableTraffic[t]) {
                            tableTraffic[t] = { table_number: t, unique_customers: new Set(), total_orders: 0 };
                        }
                        tableTraffic[t].unique_customers.add(o.customer_token);
                        tableTraffic[t].total_orders++;
                    });
                    const tableStats = Object.values(tableTraffic).map(stat => ({
                        table_number: stat.table_number,
                        unique_customers: stat.unique_customers.size,
                        total_orders: stat.total_orders
                    })).sort((a,b) => b.total_orders - a.total_orders).slice(0, 15);

                    // 5. Single vs Group Traffic
                    const groupCheck = {};
                    nonCancelled.forEach(o => {
                        const d = o.order_date.slice(0, 10);
                        const key = `${d}_${o.table_number}`;
                        if (!groupCheck[key]) groupCheck[key] = new Set();
                        groupCheck[key].add(o.customer_token);
                    });
                    let singleCount = 0;
                    let groupCount = 0;
                    Object.values(groupCheck).forEach(tokens => {
                        if (tokens.size > 1) groupCount++;
                        else singleCount++;
                    });

                    return mockResponse({
                        success: true,
                        data: {
                            revenue_sales: {
                                timeline: timelineRev,
                                gross_revenue: parseFloat(grossRev.toFixed(2)),
                                net_profit: parseFloat((grossRev * 0.85).toFixed(2)),
                                aov: parseFloat(aov.toFixed(2))
                            },
                            order_volume: {
                                timeline: timelineVol,
                                total_orders: orders.length,
                                cancelled_orders: cancelledOrders
                            },
                            menu_performance: {
                                top_items: topItems,
                                category_revenue: catRevenue
                            },
                            table_traffic: {
                                table_stats: tableStats,
                                single_count: singleCount,
                                group_count: groupCount
                            }
                        }
                    });
                }
            } catch(e) {
                console.error(e);
                return mockResponse({ success: false, message: e.message }, 500);
            }
        }

        // Default: Route to original fetch if it is not one of our PHP files
        return originalFetch(url, options);
    };
})();
