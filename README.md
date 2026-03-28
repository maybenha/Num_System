PHP NUM_Starbuck Coffee Management System

This project is a PHP-based coffee system with two user roles: Admin and User, implementing sessions and cookies for secure login and logout.

Main Features
Authentication System
User registration and login for both Admin and Customer
Session management after login
Cookie support for remembering login state
Logout functionality that clears session and cookies
User Role Management
Admin Role
Manage users (disable account or delete account)
Add, update, and manage products
View customer orders
User Role
Register and log in
Browse products loaded from the database
Add products to personal cart
Product Management
Products contain:
id
name
quantity
price
image_url
Products are loaded directly from the database
Admin can insert and manage products
Shopping Cart System
Customers can add products from the store to their own cart
Cart data is stored temporarily as an array
When the customer clicks Checkout, the order is saved to the database
Customer Information
Customer data contains:
id
full_name
phone
Database Integration
All customer, product, and order data are retrieved from the database
No hardcoded product insertion inside functions
UI Design
Follow the same interface style as the provided design image
