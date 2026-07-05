-- TABLE: categories
INSERT INTO "categories" ("category_id", "category_name", "description") VALUES ('1', 'Appetizer', NULL);
INSERT INTO "categories" ("category_id", "category_name", "description") VALUES ('2', 'Main Course', NULL);
INSERT INTO "categories" ("category_id", "category_name", "description") VALUES ('3', 'Dessert', NULL);
INSERT INTO "categories" ("category_id", "category_name", "description") VALUES ('4', 'Beverage', NULL);

-- TABLE: menu_items
INSERT INTO "menu_items" ("item_id", "category_id", "item_name", "description", "price", "image_url", "is_available") VALUES ('12', '2', 'Biriyani', 'The Rice: Uses long, fluffy Basmati rice, flavored with whole spices like cardamom, cloves, and cinnamon, and often tinted bright yellow or orange with saffron.The Meat: Tender pieces of meat (usually chicken, beef, mutton, or fish) are heavily marinated in yogurt and spices, then either cooked alongside the rice or prepared as a rich gravy.Garnishes: It is typically topped with caramelized onions, fried cashews, and fresh herbs.Accompaniments: It is usually served with a side of mint sambal, raita (a creamy yogurt dip), and a boiled egg', '1500.00', 'uploads/menu_6a40ccdb2b7f5.jpg', '1');
INSERT INTO "menu_items" ("item_id", "category_id", "item_name", "description", "price", "image_url", "is_available") VALUES ('13', '3', 'Cheese Cake', 'Texture: Dense, smooth, and velvety, though it can range from light and fluffy to incredibly rich depending on the style.Preparation: It can be baked (firm and custard-like) or unbaked (set entirely by refrigeration).Flavor: Slightly tangy from the cheese but balanced with sugar and often enhanced with vanilla, lemon, or other extracts.', '560.00', 'uploads/menu_6a40cc9a0d6ea.jpg', '1');
INSERT INTO "menu_items" ("item_id", "category_id", "item_name", "description", "price", "image_url", "is_available") VALUES ('14', '4', 'Mojito', 'White Rum: The base spirit, giving the drink its kick.Fresh Lime Juice: Provides a bright, tart citrus flavor.Sugar or Simple Syrup: Adds the necessary sweetness to balance the tart lime.Fresh Mint: The star of the drink; adds an aromatic, herbaceous taste.Soda Water:', '250.00', 'uploads/menu_6a40cc4044e51.jpg', '1');
INSERT INTO "menu_items" ("item_id", "category_id", "item_name", "description", "price", "image_url", "is_available") VALUES ('15', '2', 'Spicy Sri Lankan Rice & Curry (Chicken / Fish / Veg)', 'The ultimate local comfort food. Fragrant basmati or red rice served with traditional dhal, crispy papadam, and your choice of authentic, fiery Sri Lankan curry.', '500.00', 'uploads/menu_6a40ccbc5ba0f.jpg', '1');
INSERT INTO "menu_items" ("item_id", "category_id", "item_name", "description", "price", "image_url", "is_available") VALUES ('16', '2', 'Cheese & Egg Kottu (Chicken / Beef)', 'A street-food classic with a premium twist! Shredded parotta roti wok-tossed with fresh veggies, eggs, tender meat, and a generous layer of melted mozzarella.', '1450.00', 'uploads/menu_6a40cca9f352b.jpg', '1');
INSERT INTO "menu_items" ("item_id", "category_id", "item_name", "description", "price", "image_url", "is_available") VALUES ('17', '1', 'Signature Crispy Chicken Burger', 'Golden, crunchy fried chicken breast topped with fresh lettuce, juicy tomatoes, cheddar cheese, and our secret homemade spicy sauce, sandwiched in a toasted brioche bun.', '990.01', 'uploads/menu_6a40cc7d48137.jpg', '1');
INSERT INTO "menu_items" ("item_id", "category_id", "item_name", "description", "price", "image_url", "is_available") VALUES ('18', '3', 'Ultimate Chocolate Lava Cake', 'A decadent chocolate cake with a warm, gooey molten chocolate center. Served with a scoop of premium vanilla ice cream.', '450.00', 'uploads/menu_6a40cc8b1a005.jpg', '1');
INSERT INTO "menu_items" ("item_id", "category_id", "item_name", "description", "price", "image_url", "is_available") VALUES ('19', '1', 'Loaded BBQ Chicken Pizza', 'BBQ sauce base topped with grilled chicken chunks, red onions, bell peppers, and bubbling mozzarella cheese on a freshly baked crispy crust.', '2560.00', 'uploads/menu_6a40cc7109042.jpg', '1');
INSERT INTO "menu_items" ("item_id", "category_id", "item_name", "description", "price", "image_url", "is_available") VALUES ('20', '1', 'Hot Butter Cuttlefish (HBC) - Portion', 'The legendary island favorite. Crispy, batter-fried cuttlefish tossed in spicy butter, spring onions, and dry chilies. Perfect for sharing!', '1750.00', 'uploads/menu_6a40cc63147ee.jpg', '1');
INSERT INTO "menu_items" ("item_id", "category_id", "item_name", "description", "price", "image_url", "is_available") VALUES ('21', '1', 'Sweet Chili Chicken Wings (6 Pcs)', 'Juicy, crispy chicken wings glazed in a sticky, sweet, and slightly spicy chili sauce. The perfect appetizer to kickstart your meal.', '1290.00', 'uploads/menu_6a40cc5273f5b.jpg', '1');
INSERT INTO "menu_items" ("item_id", "category_id", "item_name", "description", "price", "image_url", "is_available") VALUES ('22', '4', 'Classic Iced Coffee', 'Rich, brewed Ceylon coffee blended with creamy milk and sweetened just right, served chilled for an instant energy boost.', '380.00', 'uploads/menu_6a40cc33ceee2.jpg', '1');
INSERT INTO "menu_items" ("item_id", "category_id", "item_name", "description", "price", "image_url", "is_available") VALUES ('23', '4', 'Mango Smoothie', 'Thick, creamy, and bursting with tropical flavor. Made with fresh, ripe mango pulp and chilled yogurt, blended to perfection.', '650.00', 'uploads/menu_6a40cc26f108c.jpg', '1');
INSERT INTO "menu_items" ("item_id", "category_id", "item_name", "description", "price", "image_url", "is_available") VALUES ('24', '4', 'Blue Lagoon Mocktail', 'A vibrant, eye-catching blue drink with a refreshing citrusy flavor, mixed with lemonade and a hint of blue curaçao syrup.', '880.00', 'uploads/menu_6a40cc1894480.jpg', '1');

-- TABLE: order_items
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('1', '2', '14', '1', '250.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('2', '3', '24', '1', '880.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('3', '3', '23', '1', '650.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('4', '4', '18', '1', '450.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('5', '5', '19', '2', '5120.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('6', '6', '22', '1', '380.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('7', '7', '22', '1', '380.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('8', '8', '22', '1', '380.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('9', '9', '22', '1', '380.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('10', '9', '23', '1', '650.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('11', '10', '23', '1', '650.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('12', '11', '24', '1', '880.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('13', '11', '23', '1', '650.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('14', '12', '23', '1', '650.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('15', '12', '24', '1', '880.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('16', '13', '24', '1', '880.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('17', '14', '23', '1', '650.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('18', '15', '24', '1', '880.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('19', '16', '21', '1', '1290.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('20', '17', '17', '1', '990.01');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('21', '18', '15', '1', '500.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('22', '19', '14', '1', '250.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('23', '19', '13', '1', '560.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('24', '19', '20', '1', '1750.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('25', '20', '23', '1', '650.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('26', '20', '22', '1', '380.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('27', '21', '19', '2', '5120.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('28', '21', '20', '1', '1750.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('29', '22', '24', '1', '880.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('30', '22', '23', '1', '650.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('31', '23', '22', '1', '380.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('32', '24', '21', '1', '1290.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('33', '24', '20', '1', '1750.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('34', '24', '23', '1', '650.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('35', '25', '24', '1', '880.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('36', '26', '24', '1', '880.00');
INSERT INTO "order_items" ("order_item_id", "order_id", "item_id", "quantity", "subtotal") VALUES ('37', '27', '24', '1', '880.00');

-- TABLE: orders
INSERT INTO "orders" ("order_id", "table_number", "session_id", "customer_token", "total_amount", "status", "bill_requested", "order_date") VALUES ('2', '4', NULL, NULL, '275.00', 'served', '0', '2026-05-31 09:58:01');
INSERT INTO "orders" ("order_id", "table_number", "session_id", "customer_token", "total_amount", "status", "bill_requested", "order_date") VALUES ('3', '3', NULL, NULL, '1683.00', 'served', '0', '2026-06-01 09:53:38');
INSERT INTO "orders" ("order_id", "table_number", "session_id", "customer_token", "total_amount", "status", "bill_requested", "order_date") VALUES ('4', '3', NULL, NULL, '495.00', 'served', '0', '2026-06-01 09:55:31');
INSERT INTO "orders" ("order_id", "table_number", "session_id", "customer_token", "total_amount", "status", "bill_requested", "order_date") VALUES ('5', '3', NULL, NULL, '5632.00', 'served', '0', '2026-06-01 09:56:05');
INSERT INTO "orders" ("order_id", "table_number", "session_id", "customer_token", "total_amount", "status", "bill_requested", "order_date") VALUES ('6', '6', NULL, NULL, '418.00', 'served', '0', '2026-06-03 09:40:04');
INSERT INTO "orders" ("order_id", "table_number", "session_id", "customer_token", "total_amount", "status", "bill_requested", "order_date") VALUES ('7', '6', NULL, NULL, '418.00', 'served', '0', '2026-06-03 09:40:44');
INSERT INTO "orders" ("order_id", "table_number", "session_id", "customer_token", "total_amount", "status", "bill_requested", "order_date") VALUES ('8', '6', NULL, NULL, '418.00', 'served', '0', '2026-06-03 09:40:54');
INSERT INTO "orders" ("order_id", "table_number", "session_id", "customer_token", "total_amount", "status", "bill_requested", "order_date") VALUES ('9', '6', NULL, NULL, '1133.00', 'served', '0', '2026-06-03 09:41:55');
INSERT INTO "orders" ("order_id", "table_number", "session_id", "customer_token", "total_amount", "status", "bill_requested", "order_date") VALUES ('10', '6', NULL, NULL, '715.00', 'served', '0', '2026-06-03 09:43:14');
INSERT INTO "orders" ("order_id", "table_number", "session_id", "customer_token", "total_amount", "status", "bill_requested", "order_date") VALUES ('11', '7', NULL, NULL, '1683.00', 'served', '0', '2026-06-03 09:55:14');
INSERT INTO "orders" ("order_id", "table_number", "session_id", "customer_token", "total_amount", "status", "bill_requested", "order_date") VALUES ('12', '7', NULL, NULL, '1683.00', 'served', '0', '2026-06-03 09:57:25');
INSERT INTO "orders" ("order_id", "table_number", "session_id", "customer_token", "total_amount", "status", "bill_requested", "order_date") VALUES ('13', '7', NULL, NULL, '968.00', 'served', '0', '2026-06-03 09:58:32');
INSERT INTO "orders" ("order_id", "table_number", "session_id", "customer_token", "total_amount", "status", "bill_requested", "order_date") VALUES ('14', '8', NULL, NULL, '715.00', 'served', '0', '2026-06-03 10:25:47');
INSERT INTO "orders" ("order_id", "table_number", "session_id", "customer_token", "total_amount", "status", "bill_requested", "order_date") VALUES ('15', '8', NULL, NULL, '968.00', 'served', '0', '2026-06-03 10:30:46');
INSERT INTO "orders" ("order_id", "table_number", "session_id", "customer_token", "total_amount", "status", "bill_requested", "order_date") VALUES ('16', '8', NULL, NULL, '1419.00', 'served', '0', '2026-06-03 10:30:54');
INSERT INTO "orders" ("order_id", "table_number", "session_id", "customer_token", "total_amount", "status", "bill_requested", "order_date") VALUES ('17', '8', NULL, NULL, '1089.01', 'served', '0', '2026-06-03 10:31:11');
INSERT INTO "orders" ("order_id", "table_number", "session_id", "customer_token", "total_amount", "status", "bill_requested", "order_date") VALUES ('18', '8', NULL, NULL, '550.00', 'served', '0', '2026-06-03 10:31:29');
INSERT INTO "orders" ("order_id", "table_number", "session_id", "customer_token", "total_amount", "status", "bill_requested", "order_date") VALUES ('19', '1', NULL, NULL, '2816.00', 'served', '0', '2026-06-21 17:13:01');
INSERT INTO "orders" ("order_id", "table_number", "session_id", "customer_token", "total_amount", "status", "bill_requested", "order_date") VALUES ('20', '2', NULL, NULL, '1133.00', 'served', '0', '2026-06-22 15:16:10');
INSERT INTO "orders" ("order_id", "table_number", "session_id", "customer_token", "total_amount", "status", "bill_requested", "order_date") VALUES ('21', '1', NULL, NULL, '7557.00', 'served', '0', '2026-06-22 15:27:46');
INSERT INTO "orders" ("order_id", "table_number", "session_id", "customer_token", "total_amount", "status", "bill_requested", "order_date") VALUES ('22', '3', NULL, 'TK-ZF84UHP-1631', '1683.00', 'served', '0', '2026-06-27 13:49:16');
INSERT INTO "orders" ("order_id", "table_number", "session_id", "customer_token", "total_amount", "status", "bill_requested", "order_date") VALUES ('23', '3', NULL, 'TK-ZF84UHP-1631', '418.00', 'served', '0', '2026-06-27 13:50:41');
INSERT INTO "orders" ("order_id", "table_number", "session_id", "customer_token", "total_amount", "status", "bill_requested", "order_date") VALUES ('24', '4', '1', 'TK-KC1QMRI-3816', '4059.00', 'served', '0', '2026-06-28 13:25:31');
INSERT INTO "orders" ("order_id", "table_number", "session_id", "customer_token", "total_amount", "status", "bill_requested", "order_date") VALUES ('25', '4', '1', 'TK-KC1QMRI-3816', '968.00', 'served', '0', '2026-06-28 13:28:49');
INSERT INTO "orders" ("order_id", "table_number", "session_id", "customer_token", "total_amount", "status", "bill_requested", "order_date") VALUES ('26', '4', '1', 'TK-KC1QMRI-3816', '968.00', 'pending', '0', '2026-06-28 13:31:26');
INSERT INTO "orders" ("order_id", "table_number", "session_id", "customer_token", "total_amount", "status", "bill_requested", "order_date") VALUES ('27', '4', '1', 'TK-KC1QMRI-3816', '968.00', 'pending', '0', '2026-06-28 13:31:56');

-- TABLE: staff
INSERT INTO "staff" ("staff_id", "full_name", "id_number", "phone_number", "role") VALUES ('1', 'dananjaya', '200424603995', '0723560843', 'waiter');
INSERT INTO "staff" ("staff_id", "full_name", "id_number", "phone_number", "role") VALUES ('2', 'nipun yasas', '200424603990', '0742323957', 'kitchen');
INSERT INTO "staff" ("staff_id", "full_name", "id_number", "phone_number", "role") VALUES ('3', 'fathima', '2004654321', '0754567651', 'waiter');
INSERT INTO "staff" ("staff_id", "full_name", "id_number", "phone_number", "role") VALUES ('4', 'sameel', '12345', '07225555555', 'kitchen');

-- TABLE: table_sessions
INSERT INTO "table_sessions" ("session_id", "table_number", "status", "opened_at", "closed_at") VALUES ('1', '4', 'active', '2026-06-28 13:25:31', NULL);

-- TABLE: tables_qr
INSERT INTO "tables_qr" ("id", "table_number", "qr_link", "qr_image_path", "created_at") VALUES ('23', '3', 'https://510f-112-134-48-190.ngrok-free.app/qr/menu.html?table=3', 'uploads/qrcodes/table_3.png', '2026-06-27 13:46:54');
INSERT INTO "tables_qr" ("id", "table_number", "qr_link", "qr_image_path", "created_at") VALUES ('24', '4', 'https://3e1e-112-134-51-84.ngrok-free.app/qr/menu.html?table=4', 'uploads/qrcodes/table_4.png', '2026-06-28 12:59:02');
INSERT INTO "tables_qr" ("id", "table_number", "qr_link", "qr_image_path", "created_at") VALUES ('25', '5', 'https://3e1e-112-134-51-84.ngrok-free.app/qr/menu.html?table=5', 'uploads/qrcodes/table_5.png', '2026-06-28 12:59:07');

-- TABLE: users
INSERT INTO "users" ("user_id", "staff_id", "username", "password", "role", "created_at") VALUES ('2', NULL, 'nipun', 'nipun123', 'admin', '2026-05-17 16:20:44');
INSERT INTO "users" ("user_id", "staff_id", "username", "password", "role", "created_at") VALUES ('3', '1', 'dananjaya', '$2y$10$h8z8g8jhfqQGBfTo.wpaRe3DkXvjriurnPbok6B7zZlk.j9LxDgmC', 'waiter', '2026-05-20 22:30:33');
INSERT INTO "users" ("user_id", "staff_id", "username", "password", "role", "created_at") VALUES ('4', '2', 'nipun yasas', '$2y$10$mxHAaPjPl3SGXpEbMr9XEu/pl6NMQ.qxvGKghtdVh9CFC/5WMprD2', 'kitchen', '2026-05-24 02:55:03');
INSERT INTO "users" ("user_id", "staff_id", "username", "password", "role", "created_at") VALUES ('5', '3', 'fathima', '$2y$10$384hYbXQDi11zvPD06wSsuIOJGGpSxhZuUBh3nKmpPgVBiliwIK2u', 'waiter', '2026-05-25 08:12:28');
INSERT INTO "users" ("user_id", "staff_id", "username", "password", "role", "created_at") VALUES ('6', '4', 'sameel', '$2y$10$MIrKNv/kOmJDkT2f6OK3Yu1OUgU2tDAN.Z5kZ7lt1.rT1n.t80qMy', 'kitchen', '2026-05-25 08:24:47');

