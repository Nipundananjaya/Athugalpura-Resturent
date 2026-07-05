-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: qr_restaurant_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO,POSTGRESQL' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table "categories"
--

DROP TABLE IF EXISTS "categories";
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE "categories" (
  "category_id" int(11) NOT NULL,
  "category_name" varchar(100) NOT NULL,
  "description" text DEFAULT NULL,
  PRIMARY KEY ("category_id"),
  UNIQUE KEY "category_name" ("category_name")
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table "categories"
--

LOCK TABLES "categories" WRITE;
/*!40000 ALTER TABLE "categories" DISABLE KEYS */;
INSERT INTO "categories" VALUES (1,'Appetizer',NULL),(2,'Main Course',NULL),(3,'Dessert',NULL),(4,'Beverage',NULL);
/*!40000 ALTER TABLE "categories" ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table "menu_items"
--

DROP TABLE IF EXISTS "menu_items";
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE "menu_items" (
  "item_id" int(11) NOT NULL,
  "category_id" int(11) DEFAULT NULL,
  "item_name" varchar(150) NOT NULL,
  "description" text DEFAULT NULL,
  "price" decimal(10,2) NOT NULL,
  "image_url" varchar(255) DEFAULT NULL,
  "is_available" tinyint(1) DEFAULT 1,
  PRIMARY KEY ("item_id"),
  KEY "category_id" ("category_id"),
  CONSTRAINT "menu_items_ibfk_1" FOREIGN KEY ("category_id") REFERENCES "categories" ("category_id") ON DELETE SET NULL
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table "menu_items"
--

LOCK TABLES "menu_items" WRITE;
/*!40000 ALTER TABLE "menu_items" DISABLE KEYS */;
INSERT INTO "menu_items" VALUES (12,2,'Biriyani','The Rice: Uses long, fluffy Basmati rice, flavored with whole spices like cardamom, cloves, and cinnamon, and often tinted bright yellow or orange with saffron.The Meat: Tender pieces of meat (usually chicken, beef, mutton, or fish) are heavily marinated in yogurt and spices, then either cooked alongside the rice or prepared as a rich gravy.Garnishes: It is typically topped with caramelized onions, fried cashews, and fresh herbs.Accompaniments: It is usually served with a side of mint sambal, raita (a creamy yogurt dip), and a boiled egg',1500.00,'uploads/menu_6a40ccdb2b7f5.jpg',1),(13,3,'Cheese Cake','Texture: Dense, smooth, and velvety, though it can range from light and fluffy to incredibly rich depending on the style.Preparation: It can be baked (firm and custard-like) or unbaked (set entirely by refrigeration).Flavor: Slightly tangy from the cheese but balanced with sugar and often enhanced with vanilla, lemon, or other extracts.',560.00,'uploads/menu_6a40cc9a0d6ea.jpg',1),(14,4,'Mojito','White Rum: The base spirit, giving the drink its kick.Fresh Lime Juice: Provides a bright, tart citrus flavor.Sugar or Simple Syrup: Adds the necessary sweetness to balance the tart lime.Fresh Mint: The star of the drink; adds an aromatic, herbaceous taste.Soda Water:',250.00,'uploads/menu_6a40cc4044e51.jpg',1),(15,2,'Spicy Sri Lankan Rice & Curry (Chicken / Fish / Veg)','The ultimate local comfort food. Fragrant basmati or red rice served with traditional dhal, crispy papadam, and your choice of authentic, fiery Sri Lankan curry.',500.00,'uploads/menu_6a40ccbc5ba0f.jpg',1),(16,2,'Cheese & Egg Kottu (Chicken / Beef)','A street-food classic with a premium twist! Shredded parotta roti wok-tossed with fresh veggies, eggs, tender meat, and a generous layer of melted mozzarella.',1450.00,'uploads/menu_6a40cca9f352b.jpg',1),(17,1,'Signature Crispy Chicken Burger','Golden, crunchy fried chicken breast topped with fresh lettuce, juicy tomatoes, cheddar cheese, and our secret homemade spicy sauce, sandwiched in a toasted brioche bun.',990.01,'uploads/menu_6a40cc7d48137.jpg',1),(18,3,'Ultimate Chocolate Lava Cake','A decadent chocolate cake with a warm, gooey molten chocolate center. Served with a scoop of premium vanilla ice cream.',450.00,'uploads/menu_6a40cc8b1a005.jpg',1),(19,1,'Loaded BBQ Chicken Pizza','BBQ sauce base topped with grilled chicken chunks, red onions, bell peppers, and bubbling mozzarella cheese on a freshly baked crispy crust.',2560.00,'uploads/menu_6a40cc7109042.jpg',1),(20,1,'Hot Butter Cuttlefish (HBC) - Portion','The legendary island favorite. Crispy, batter-fried cuttlefish tossed in spicy butter, spring onions, and dry chilies. Perfect for sharing!',1750.00,'uploads/menu_6a40cc63147ee.jpg',1),(21,1,'Sweet Chili Chicken Wings (6 Pcs)','Juicy, crispy chicken wings glazed in a sticky, sweet, and slightly spicy chili sauce. The perfect appetizer to kickstart your meal.',1290.00,'uploads/menu_6a40cc5273f5b.jpg',1),(22,4,'Classic Iced Coffee','Rich, brewed Ceylon coffee blended with creamy milk and sweetened just right, served chilled for an instant energy boost.',380.00,'uploads/menu_6a40cc33ceee2.jpg',1),(23,4,'Mango Smoothie','Thick, creamy, and bursting with tropical flavor. Made with fresh, ripe mango pulp and chilled yogurt, blended to perfection.',650.00,'uploads/menu_6a40cc26f108c.jpg',1),(24,4,'Blue Lagoon Mocktail','A vibrant, eye-catching blue drink with a refreshing citrusy flavor, mixed with lemonade and a hint of blue curaçao syrup.',880.00,'uploads/menu_6a40cc1894480.jpg',1);
/*!40000 ALTER TABLE "menu_items" ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table "order_items"
--

DROP TABLE IF EXISTS "order_items";
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE "order_items" (
  "order_item_id" int(11) NOT NULL,
  "order_id" int(11) DEFAULT NULL,
  "item_id" int(11) DEFAULT NULL,
  "quantity" int(11) NOT NULL,
  "subtotal" decimal(10,2) NOT NULL,
  PRIMARY KEY ("order_item_id"),
  KEY "order_id" ("order_id"),
  KEY "item_id" ("item_id"),
  CONSTRAINT "order_items_ibfk_1" FOREIGN KEY ("order_id") REFERENCES "orders" ("order_id") ON DELETE CASCADE,
  CONSTRAINT "order_items_ibfk_2" FOREIGN KEY ("item_id") REFERENCES "menu_items" ("item_id")
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table "order_items"
--

LOCK TABLES "order_items" WRITE;
/*!40000 ALTER TABLE "order_items" DISABLE KEYS */;
INSERT INTO "order_items" VALUES (1,2,14,1,250.00),(2,3,24,1,880.00),(3,3,23,1,650.00),(4,4,18,1,450.00),(5,5,19,2,5120.00),(6,6,22,1,380.00),(7,7,22,1,380.00),(8,8,22,1,380.00),(9,9,22,1,380.00),(10,9,23,1,650.00),(11,10,23,1,650.00),(12,11,24,1,880.00),(13,11,23,1,650.00),(14,12,23,1,650.00),(15,12,24,1,880.00),(16,13,24,1,880.00),(17,14,23,1,650.00),(18,15,24,1,880.00),(19,16,21,1,1290.00),(20,17,17,1,990.01),(21,18,15,1,500.00),(22,19,14,1,250.00),(23,19,13,1,560.00),(24,19,20,1,1750.00),(25,20,23,1,650.00),(26,20,22,1,380.00),(27,21,19,2,5120.00),(28,21,20,1,1750.00),(29,22,24,1,880.00),(30,22,23,1,650.00),(31,23,22,1,380.00),(32,24,21,1,1290.00),(33,24,20,1,1750.00),(34,24,23,1,650.00),(35,25,24,1,880.00),(36,26,24,1,880.00),(37,27,24,1,880.00);
/*!40000 ALTER TABLE "order_items" ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table "orders"
--

DROP TABLE IF EXISTS "orders";
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE "orders" (
  "order_id" int(11) NOT NULL,
  "table_number" int(11) NOT NULL,
  "session_id" int(11) DEFAULT NULL,
  "customer_token" varchar(255) DEFAULT NULL,
  "total_amount" decimal(10,2) DEFAULT 0.00,
  "status" enum('pending','preparing','ready','served','cancelled') DEFAULT 'pending',
  "bill_requested" tinyint(1) NOT NULL DEFAULT 0,
  "order_date" timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY ("order_id"),
  KEY "idx_session" ("session_id")
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table "orders"
--

LOCK TABLES "orders" WRITE;
/*!40000 ALTER TABLE "orders" DISABLE KEYS */;
INSERT INTO "orders" VALUES (2,4,NULL,NULL,275.00,'served',0,'2026-05-31 04:28:01'),(3,3,NULL,NULL,1683.00,'served',0,'2026-06-01 04:23:38'),(4,3,NULL,NULL,495.00,'served',0,'2026-06-01 04:25:31'),(5,3,NULL,NULL,5632.00,'served',0,'2026-06-01 04:26:05'),(6,6,NULL,NULL,418.00,'served',0,'2026-06-03 04:10:04'),(7,6,NULL,NULL,418.00,'served',0,'2026-06-03 04:10:44'),(8,6,NULL,NULL,418.00,'served',0,'2026-06-03 04:10:54'),(9,6,NULL,NULL,1133.00,'served',0,'2026-06-03 04:11:55'),(10,6,NULL,NULL,715.00,'served',0,'2026-06-03 04:13:14'),(11,7,NULL,NULL,1683.00,'served',0,'2026-06-03 04:25:14'),(12,7,NULL,NULL,1683.00,'served',0,'2026-06-03 04:27:25'),(13,7,NULL,NULL,968.00,'served',0,'2026-06-03 04:28:32'),(14,8,NULL,NULL,715.00,'served',0,'2026-06-03 04:55:47'),(15,8,NULL,NULL,968.00,'served',0,'2026-06-03 05:00:46'),(16,8,NULL,NULL,1419.00,'served',0,'2026-06-03 05:00:54'),(17,8,NULL,NULL,1089.01,'served',0,'2026-06-03 05:01:11'),(18,8,NULL,NULL,550.00,'served',0,'2026-06-03 05:01:29'),(19,1,NULL,NULL,2816.00,'served',0,'2026-06-21 11:43:01'),(20,2,NULL,NULL,1133.00,'served',0,'2026-06-22 09:46:10'),(21,1,NULL,NULL,7557.00,'served',0,'2026-06-22 09:57:46'),(22,3,NULL,'TK-ZF84UHP-1631',1683.00,'served',0,'2026-06-27 08:19:16'),(23,3,NULL,'TK-ZF84UHP-1631',418.00,'served',0,'2026-06-27 08:20:41'),(24,4,1,'TK-KC1QMRI-3816',4059.00,'served',0,'2026-06-28 07:55:31'),(25,4,1,'TK-KC1QMRI-3816',968.00,'served',0,'2026-06-28 07:58:49'),(26,4,1,'TK-KC1QMRI-3816',968.00,'pending',0,'2026-06-28 08:01:26'),(27,4,1,'TK-KC1QMRI-3816',968.00,'pending',0,'2026-06-28 08:01:56');
/*!40000 ALTER TABLE "orders" ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table "staff"
--

DROP TABLE IF EXISTS "staff";
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE "staff" (
  "staff_id" int(11) NOT NULL,
  "full_name" varchar(100) NOT NULL,
  "id_number" varchar(20) NOT NULL,
  "phone_number" varchar(15) NOT NULL,
  "role" enum('admin','waiter','kitchen') NOT NULL,
  PRIMARY KEY ("staff_id"),
  UNIQUE KEY "id_number" ("id_number")
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table "staff"
--

LOCK TABLES "staff" WRITE;
/*!40000 ALTER TABLE "staff" DISABLE KEYS */;
INSERT INTO "staff" VALUES (1,'dananjaya','200424603995','0723560843','waiter'),(2,'nipun yasas','200424603990','0742323957','kitchen'),(3,'fathima','2004654321','0754567651','waiter'),(4,'sameel','12345','07225555555','kitchen');
/*!40000 ALTER TABLE "staff" ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table "table_sessions"
--

DROP TABLE IF EXISTS "table_sessions";
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE "table_sessions" (
  "session_id" int(11) NOT NULL,
  "table_number" int(11) NOT NULL,
  "status" enum('active','closed') DEFAULT 'active',
  "opened_at" timestamp NOT NULL DEFAULT current_timestamp(),
  "closed_at" timestamp NULL DEFAULT NULL,
  PRIMARY KEY ("session_id"),
  KEY "idx_table_status" ("table_number","status")
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table "table_sessions"
--

LOCK TABLES "table_sessions" WRITE;
/*!40000 ALTER TABLE "table_sessions" DISABLE KEYS */;
INSERT INTO "table_sessions" VALUES (1,4,'active','2026-06-28 07:55:31',NULL);
/*!40000 ALTER TABLE "table_sessions" ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table "tables_qr"
--

DROP TABLE IF EXISTS "tables_qr";
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE "tables_qr" (
  "id" int(11) NOT NULL,
  "table_number" int(11) NOT NULL,
  "qr_link" varchar(255) NOT NULL,
  "qr_image_path" varchar(255) NOT NULL,
  "created_at" timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY ("id"),
  UNIQUE KEY "table_number" ("table_number")
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table "tables_qr"
--

LOCK TABLES "tables_qr" WRITE;
/*!40000 ALTER TABLE "tables_qr" DISABLE KEYS */;
INSERT INTO "tables_qr" VALUES (23,3,'https://510f-112-134-48-190.ngrok-free.app/qr/menu.html?table=3','uploads/qrcodes/table_3.png','2026-06-27 08:16:54'),(24,4,'https://3e1e-112-134-51-84.ngrok-free.app/qr/menu.html?table=4','uploads/qrcodes/table_4.png','2026-06-28 07:29:02'),(25,5,'https://3e1e-112-134-51-84.ngrok-free.app/qr/menu.html?table=5','uploads/qrcodes/table_5.png','2026-06-28 07:29:07');
/*!40000 ALTER TABLE "tables_qr" ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table "users"
--

DROP TABLE IF EXISTS "users";
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE "users" (
  "user_id" int(11) NOT NULL,
  "staff_id" int(11) DEFAULT NULL,
  "username" varchar(50) NOT NULL,
  "password" varchar(255) NOT NULL,
  "role" enum('admin','waiter','kitchen') NOT NULL,
  "created_at" timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY ("user_id"),
  UNIQUE KEY "username" ("username"),
  KEY "fk_user_staff" ("staff_id"),
  CONSTRAINT "fk_user_staff" FOREIGN KEY ("staff_id") REFERENCES "staff" ("staff_id") ON DELETE CASCADE
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table "users"
--

LOCK TABLES "users" WRITE;
/*!40000 ALTER TABLE "users" DISABLE KEYS */;
INSERT INTO "users" VALUES (2,NULL,'nipun','nipun123','admin','2026-05-17 10:50:44'),(3,1,'dananjaya','$2y$10$h8z8g8jhfqQGBfTo.wpaRe3DkXvjriurnPbok6B7zZlk.j9LxDgmC','waiter','2026-05-20 17:00:33'),(4,2,'nipun yasas','$2y$10$mxHAaPjPl3SGXpEbMr9XEu/pl6NMQ.qxvGKghtdVh9CFC/5WMprD2','kitchen','2026-05-23 21:25:03'),(5,3,'fathima','$2y$10$384hYbXQDi11zvPD06wSsuIOJGGpSxhZuUBh3nKmpPgVBiliwIK2u','waiter','2026-05-25 02:42:28'),(6,4,'sameel','$2y$10$MIrKNv/kOmJDkT2f6OK3Yu1OUgU2tDAN.Z5kZ7lt1.rT1n.t80qMy','kitchen','2026-05-25 02:54:47');
/*!40000 ALTER TABLE "users" ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-05 11:50:58
