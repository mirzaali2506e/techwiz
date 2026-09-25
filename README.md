# MarketLink — PHP + MySQL

This implementation is based on the supplied MarketLink SRS. The SRS requires a responsive full-stack web application connecting customers with farmers, product search/filtering, pre-orders for pickup, dashboards, reviews, role-based access, admin controls and map-based market locations. It explicitly excludes online payment and delivery. See the supplied SRS for the authoritative requirements.

## Stack
- PHP 8+
- MySQL 8+
- PDO prepared statements
- Bootstrap 5
- Leaflet + OpenStreetMap
- XAMPP-compatible

## Install
1. Copy the `marketlink_php` folder into `xampp/htdocs/`.
2. Start Apache and MySQL in XAMPP.
3. Open phpMyAdmin and import `database.sql`.
4. Check `config/config.php` for DB host/name/user/password.
5. Visit `http://localhost/marketlink_php/`.

## Demo accounts
- Admin: admin@marketlink.com / MarketLink@123
- Farmer: farmer@marketlink.com / MarketLink@123
- Customer: customer@marketlink.com / MarketLink@123

## Main flows
Customer: register/login → browse/filter products → product details → cart → choose market + pickup time → pre-order → order history → review.
Farmer: login → dashboard → add weekly stock → view incoming orders → update order status.
Admin: login → dashboard → approve/suspend farmers and view platform metrics.
Markets: market cards + interactive OpenStreetMap markers.

## Notes
- No online payment gateway is implemented because the SRS says payment is settled in person at pickup.
- Delivery/courier is not implemented because it is outside scope.
- Farmer verification/certification is not implemented because the SRS excludes it.
- Product images can be added later by extending the products table and upload handling.
- AI assistant is optional in the SRS and is not required for the core build.


### Product images
The demo products use real product photographs loaded from public image hosts. The image mapping is in `config/config.php` inside `product_image()`.


### Product photos
Product cards use stable direct Unsplash photograph URLs instead of the previous random image service, so the images are not dependent on a dynamic/random endpoint.


### IMPORTANT SETUP
Open `http://localhost/marketlink_php/setup.php` once and click **Install / Reset Database**. This imports the complete `database.sql`, creates the demo accounts, markets and all product rows. Product photos are selected from real-photo search tags based on the exact product name; an image fallback is used only if the remote photo service is unavailable.


## Product image upload
Farmers can upload a product photo when adding a product from Farmer Dashboard. JPG/PNG/WEBP, max 5MB. Uploaded files are stored locally in `assets/uploads/products/` and the path is saved in `products.image_url`, so the product photo works offline.
