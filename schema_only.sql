DROP TABLE IF EXISTS "order_items" CASCADE;
DROP TABLE IF EXISTS "orders" CASCADE;
DROP TABLE IF EXISTS "menu_items" CASCADE;
DROP TABLE IF EXISTS "categories" CASCADE;
DROP TABLE IF EXISTS "users" CASCADE;
DROP TABLE IF EXISTS "staff" CASCADE;
DROP TABLE IF EXISTS "table_sessions" CASCADE;
DROP TABLE IF EXISTS "tables_qr" CASCADE;

CREATE TABLE "categories" (
  "category_id" SERIAL PRIMARY KEY,
  "category_name" VARCHAR(100) NOT NULL UNIQUE,
  "description" TEXT DEFAULT NULL
);

CREATE TABLE "menu_items" (
  "item_id" SERIAL PRIMARY KEY,
  "category_id" INTEGER DEFAULT NULL REFERENCES "categories"("category_id") ON DELETE SET NULL,
  "item_name" VARCHAR(150) NOT NULL,
  "description" TEXT DEFAULT NULL,
  "price" NUMERIC(10,2) NOT NULL,
  "image_url" VARCHAR(255) DEFAULT NULL,
  "is_available" BOOLEAN DEFAULT TRUE
);

CREATE TABLE "staff" (
  "staff_id" SERIAL PRIMARY KEY,
  "full_name" VARCHAR(100) NOT NULL,
  "id_number" VARCHAR(20) NOT NULL UNIQUE,
  "phone_number" VARCHAR(15) NOT NULL,
  "role" VARCHAR(20) NOT NULL CHECK (role IN ('admin','waiter','kitchen'))
);

CREATE TABLE "users" (
  "user_id" SERIAL PRIMARY KEY,
  "staff_id" INTEGER DEFAULT NULL REFERENCES "staff"("staff_id") ON DELETE CASCADE,
  "username" VARCHAR(50) NOT NULL UNIQUE,
  "password" VARCHAR(255) NOT NULL,
  "role" VARCHAR(20) NOT NULL CHECK (role IN ('admin','waiter','kitchen')),
  "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE "table_sessions" (
  "session_id" SERIAL PRIMARY KEY,
  "table_number" INTEGER NOT NULL,
  "status" VARCHAR(10) DEFAULT 'active' CHECK (status IN ('active','closed')),
  "opened_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "closed_at" TIMESTAMP NULL DEFAULT NULL
);

CREATE TABLE "orders" (
  "order_id" SERIAL PRIMARY KEY,
  "table_number" INTEGER NOT NULL,
  "session_id" INTEGER DEFAULT NULL REFERENCES "table_sessions"("session_id"),
  "customer_token" VARCHAR(255) DEFAULT NULL,
  "total_amount" NUMERIC(10,2) DEFAULT 0.00,
  "status" VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending','preparing','ready','served','cancelled')),
  "bill_requested" BOOLEAN NOT NULL DEFAULT FALSE,
  "order_date" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE "order_items" (
  "order_item_id" SERIAL PRIMARY KEY,
  "order_id" INTEGER DEFAULT NULL REFERENCES "orders"("order_id") ON DELETE CASCADE,
  "item_id" INTEGER DEFAULT NULL REFERENCES "menu_items"("item_id"),
  "quantity" INTEGER NOT NULL,
  "subtotal" NUMERIC(10,2) NOT NULL
);

CREATE TABLE "tables_qr" (
  "id" SERIAL PRIMARY KEY,
  "table_number" INTEGER NOT NULL UNIQUE,
  "qr_link" VARCHAR(255) NOT NULL,
  "qr_image_path" VARCHAR(255) NOT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
