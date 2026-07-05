import re

with open("c:/Users/Danan/OneDrive/Desktop/QR foods/customer menu.html", "r", encoding="utf-8") as f:
    content = f.read()

new_css = """<style>
/* ===== RESET ===== */
*,*::before,*::after {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

/* ===== COLORS ===== */
:root {
  --bg: #F0F2F8;
  --primary: #FF4B2B;
  --primary-d: #e03a1c;
  --heading: #2B2E4A;
  --body: #1A1A1B;
  --muted: #6B6F8A;
  --border: #E2E4ED;
  --white: #ffffff;
  --card: #ffffff;
  --card2: #F8F9FD;
  --radius: 12px;
}

/* ===== BODY ===== */
body {
  font-family: 'Segoe UI', system-ui, sans-serif;
  background: var(--bg);
  color: var(--body);
  min-height: 100vh;
  padding-bottom: 120px;
}

/* ===== TOP BAR ===== */
.topbar {
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid var(--border);
  padding: 15px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 2px 10px rgba(0,0,0,0.02);
}
.brand {
  font-size: 18px;
  font-weight: 800;
  color: var(--heading);
}
.brand span {
  color: var(--primary);
}
.topbar-right {
  display: flex;
  align-items: center;
  gap: 12px;
}
.table-pill {
  background: rgba(255, 75, 43, 0.1);
  color: var(--primary);
  font-size: 12px;
  font-weight: 700;
  padding: 6px 14px;
  border-radius: 20px;
}
.cart-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 8px 16px;
  color: var(--heading);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 2px 5px rgba(0,0,0,0.02);
}
.cart-btn:hover {
  border-color: var(--primary);
  color: var(--primary);
}
.cart-badge {
  background: var(--primary);
  color: var(--white);
  font-size: 11px;
  font-weight: 800;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 5px rgba(255,75,43,0.3);
}

/* ===== CATEGORY TABS ===== */
.cat-scroll {
  padding: 20px 20px 0;
  overflow-x: auto;
  scrollbar-width: none;
}
.cat-scroll::-webkit-scrollbar {
  display: none;
}
.cat-tabs {
  display: flex;
  gap: 10px;
  white-space: nowrap;
}
.cat-tab {
  padding: 8px 20px;
  border-radius: 25px;
  background: var(--white);
  border: 1px solid var(--border);
  color: var(--muted);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
  user-select: none;
  box-shadow: 0 2px 4px rgba(0,0,0,0.01);
}
.cat-tab:hover {
  background: rgba(255, 75, 43, 0.05);
  color: var(--primary);
  border-color: rgba(255, 75, 43, 0.2);
}
.cat-tab.active {
  background: var(--primary);
  color: var(--white);
  border-color: var(--primary);
  box-shadow: 0 4px 12px rgba(255, 75, 43, 0.25);
}

/* ===== MENU SECTION ===== */
.menu-wrap {
  padding: 25px 20px;
}
.cat-block {
  margin-bottom: 35px;
}
.cat-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--heading);
  letter-spacing: 0.5px;
  text-transform: uppercase;
  padding-bottom: 10px;
  margin-bottom: 15px;
  border-bottom: 2px solid var(--border);
}
.menu-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 15px;
}

/* ===== MENU CARD ===== */
.menu-card {
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  box-shadow: 0 4px 15px rgba(0,0,0,0.02);
}
.menu-card:hover {
  border-color: var(--primary);
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(255, 75, 43, 0.1);
}
.card-img {
  height: 120px;
  background: #F8F9FD;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 50px;
  border-bottom: 1px solid var(--border);
  transition: transform 0.3s;
}
.menu-card:hover .card-img {
  transform: scale(1.05);
}
.card-body {
  padding: 15px;
  background: var(--white);
  position: relative;
  z-index: 2;
}
.card-name {
  font-weight: 700;
  font-size: 14px;
  color: var(--heading);
  line-height: 1.3;
  margin-bottom: 5px;
}
.card-desc {
  font-size: 12px;
  color: var(--muted);
  line-height: 1.4;
  margin-bottom: 12px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.card-foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.card-price {
  font-size: 16px;
  font-weight: 800;
  color: var(--primary);
}
.add-btn {
  width: 32px;
  height: 32px;
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: 8px;
  color: var(--heading);
  font-size: 20px;
  line-height: 1;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}
.menu-card:hover .add-btn {
  background: var(--primary);
  color: var(--white);
  border-color: var(--primary);
}
.add-btn:active {
  transform: scale(0.9);
}

/* ===== DARK OVERLAY (behind cart) ===== */
.overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(4px);
  z-index: 200;
  opacity: 0;
  transition: opacity 0.3s ease;
}
.overlay.open {
  display: block;
  opacity: 1;
}

/* ===== CART BOTTOM SHEET ===== */
.cart-sheet {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  max-height: 85vh;
  background: var(--white);
  border-radius: 24px 24px 0 0;
  box-shadow: 0 -10px 40px rgba(0,0,0,0.1);
  overflow-y: auto;
  z-index: 201;
  padding: 0 24px 30px;
  transform: translateY(100%);
  transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.cart-sheet.open {
  transform: translateY(0);
}
.drag-bar {
  width: 40px;
  height: 5px;
  background: #D0D4E4;
  border-radius: 3px;
  margin: 15px auto 20px;
}
.sheet-head {
  font-size: 20px;
  font-weight: 800;
  color: var(--heading);
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

/* ===== CART ITEM ROW ===== */
.cart-row {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 15px 0;
  border-bottom: 1px solid var(--border);
}
.cr-emo {
  font-size: 30px;
  width: 45px;
  height: 45px;
  background: #F8F9FD;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.cr-info {
  flex: 1;
}
.cr-name {
  font-weight: 700;
  font-size: 14px;
  color: var(--heading);
}
.cr-unit {
  font-size: 12px;
  color: var(--muted);
  margin-top: 4px;
}
.qty-ctrl {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #F8F9FD;
  border-radius: 8px;
  padding: 4px;
}
.q-btn {
  width: 28px;
  height: 28px;
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: 6px;
  color: var(--heading);
  font-size: 16px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}
.q-btn:hover {
  border-color: var(--primary);
  color: var(--primary);
}
.q-num {
  font-weight: 700;
  font-size: 14px;
  min-width: 20px;
  text-align: center;
  color: var(--heading);
}
.cr-tot {
  font-weight: 800;
  font-size: 14px;
  min-width: 60px;
  text-align: right;
  color: var(--primary);
}

/* ===== BILL SUMMARY ===== */
.bill {
  background: #F8F9FD;
  border-radius: 12px;
  padding: 20px;
  margin: 20px 0;
  border: 1px dashed #D0D4E4;
}
.bill-row {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
  color: var(--muted);
  padding: 6px 0;
  font-weight: 500;
}
.bill-total {
  display: flex;
  justify-content: space-between;
  font-size: 18px;
  font-weight: 800;
  padding-top: 15px;
  margin-top: 10px;
  border-top: 2px solid var(--border);
  color: var(--heading);
}
.bill-total span:last-child {
  color: var(--primary);
}

/* ===== PLACE ORDER BUTTON ===== */
.order-btn {
  width: 100%;
  padding: 16px;
  background: linear-gradient(135deg, var(--primary) 0%, #FF416C 100%);
  color: #fff;
  border: none;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 8px 20px rgba(255, 75, 43, 0.25);
}
.order-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 25px rgba(255, 75, 43, 0.35);
}

/* ===== EMPTY CART ===== */
.empty {
  text-align: center;
  padding: 50px 0;
  color: var(--muted);
  font-size: 15px;
  font-weight: 500;
}
.empty span {
  font-size: 60px;
  display: block;
  margin-bottom: 15px;
  opacity: 0.5;
}

/* ===== ORDER SUCCESS SCREEN ===== */
.success {
  display: none;
  position: fixed;
  inset: 0;
  background: var(--bg);
  z-index: 300;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 40px;
}
.success.show {
  display: flex;
}
.success-icon {
  font-size: 80px;
  margin-bottom: 20px;
  animation: bounceIn 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
@keyframes bounceIn {
  0% { transform: scale(0); opacity: 0; }
  50% { transform: scale(1.2); opacity: 1; }
  100% { transform: scale(1); opacity: 1; }
}
.success h2 {
  font-size: 26px;
  font-weight: 800;
  color: var(--heading);
  margin-bottom: 10px;
}
.success p {
  color: var(--muted);
  font-size: 15px;
  line-height: 1.6;
}
.oid {
  background: var(--white);
  border: 2px dashed var(--primary);
  border-radius: 12px;
  padding: 15px 30px;
  margin: 25px 0;
  font-size: 24px;
  font-weight: 800;
  color: var(--primary);
  box-shadow: 0 4px 15px rgba(255,75,43,0.1);
}
.more-btn {
  padding: 14px 30px;
  background: var(--primary);
  color: var(--white);
  border: none;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  margin-top: 10px;
  box-shadow: 0 4px 15px rgba(255,75,43,0.2);
  transition: transform 0.2s;
}
.more-btn:hover {
  transform: translateY(-2px);
}

/* ===== TOAST NOTIFICATION ===== */
.toast {
  position: fixed;
  bottom: 30px;
  left: 50%;
  transform: translateX(-50%) translateY(100px);
  background: var(--heading);
  color: var(--white);
  border-radius: 10px;
  padding: 12px 24px;
  font-size: 14px;
  font-weight: 600;
  white-space: nowrap;
  z-index: 400;
  transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  pointer-events: none;
  box-shadow: 0 10px 30px rgba(43,46,74,0.3);
  display: flex;
  align-items: center;
  gap: 10px;
}
.toast.show {
  transform: translateX(-50%) translateY(0);
}

/* ===== RESPONSIVE ===== */
@media (min-width: 480px) {
  .menu-grid {
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  }
}
@media (min-width: 768px) {
  .menu-grid {
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 25px;
  }
  .menu-wrap {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px;
  }
  .cart-sheet {
    max-width: 450px;
    left: auto;
    right: 20px;
    bottom: 20px;
    border-radius: 24px;
    transform: translateX(120%);
    box-shadow: 0 10px 50px rgba(0,0,0,0.15);
  }
  .cart-sheet.open {
    transform: translateX(0);
  }
  .drag-bar {
    display: none;
  }
  .sheet-head {
    margin-top: 25px;
  }
}
</style>"""

content = re.sub(r"<style>.*?</style>", new_css, content, flags=re.DOTALL)

with open("c:/Users/Danan/OneDrive/Desktop/QR foods/customer menu.html", "w", encoding="utf-8") as f:
    f.write(content)

print("Done")
