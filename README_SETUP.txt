QUICKBITE - FIXED PROJECT

1. Copy the complete Quickbite1 folder into your XAMPP htdocs folder:
   C:\xampp\htdocs\Quickbite1

2. Start Apache and MySQL in XAMPP.

3. Open phpMyAdmin: http://localhost/phpmyadmin/
   - For a NEW installation: import database/database.sql.
   - If you already have quickbite_db with student/order data: select quickbite_db and import database/update_existing_database.sql. This preserves existing data while adding the missing order columns and order_items table.

4. Open: http://localhost/Quickbite1/

Database connection defaults (edit includes/db.php if your MySQL credentials differ):
   host=localhost, username=root, password=(blank), database=quickbite_db

Admin demo login:
   username: admin
   password: admin123
   Change this password for any real deployment.

Notes:
- All accidental Markdown code fences have been removed from PHP files.
- Database schema now includes order_items, pickup_time, payment_method, and payment_status.
- Receipt links and receipt parameter handling are consistent.
- PHP syntax was checked for every PHP file.
- This package cannot verify your local XAMPP configuration; if localhost still spins, check Apache/MySQL status and the Apache/PHP error log.
