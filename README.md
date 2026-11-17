# OyePick Store

WooCommerce website for **OyePick**, a Karachi store selling apparel, gadgets and home essentials with cash on delivery all over Pakistan.

## Review it

| What | Link |
|---|---|
| **Live staging store (recommended).** Full WordPress + WooCommerce on real hosting. | [thewebsiteagency.net/oyepick](https://thewebsiteagency.net/oyepick/) |
| **Full WordPress + WooCommerce demo.** Real add to cart, cart drawer, checkout (COD), product reviews and purchase notifications. Opens a private WordPress site in your browser. First load takes about 1–2 minutes. | [Open live WordPress demo](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/bilalce109/oyepick/main/wordpress/blueprint.json) |
| **Design preview.** A static homepage that loads instantly. | [Open design preview](https://bilalce109.github.io/oyepick/) |

The demo runs only in your browser. Nothing you do there touches the live site, and it resets when the tab is closed.

### What to check in the demo

1. **Homepage.** The animated banner has 4 product slides that change every 6 seconds and support swipe, arrows and dots. Below it: category cards, 12 products with filter tabs, the promo banner and the reviews column.
2. **Add to cart.** Click **Add** on any product. The cart drawer slides in without a page reload, and the cart counter in the header updates.
3. **Checkout.** Use Cart → Checkout and pay with **Cash on Delivery**.
4. **Purchase notification.** A "Demo from Lahore purchased …" card appears bottom-left about 6 seconds after the page opens, then every **25 seconds**. In the demo it uses 3 test orders named "Demo". On the live site it shows **real orders only**, with the customer's first name and city and nothing else.
5. **Reviews.** Open a product, scroll to **Customer reviews** and leave a rating and review. As admin, approve it under Products → Reviews. It then appears on the product page and in the homepage reviews column. Customers who ordered the product get a "Verified buyer" badge.
6. **Social links.** Instagram, Facebook and TikTok are in the top bar, the "Follow us" box and the footer. The WhatsApp button is bottom-right.
7. **Admin.** You are logged in as admin. Store settings are under **Appearance → Customize → OyePick Store**: WhatsApp number, social links, top bar text, and the notification on/off switch and interval.

## What's in this repo

```
index.html, images/          Static design preview (GitHub Pages)
wordpress/oyepick-theme/     WordPress theme (WooCommerce)
wordpress/oyepick-store-setup/  Plugin: one-click store setup + 12 products with photos
wordpress/blueprint.json     WordPress Playground recipe used by the demo link
```

### Theme features
- Animated hero slider. Prices and Add to cart come live from WooCommerce by SKU.
- AJAX add to cart with a slide-in cart drawer (WooCommerce mini-cart) and a live header counter.
- Product cards on the homepage, shop, categories and related products. Products without a price show **"Price on WhatsApp"** with a prefilled WhatsApp message.
- **Customer reviews column** on the homepage, showing real approved reviews with stars, "Verified buyer" and product. Reviews on the product page get a full-width section instead of a hidden tab.
- **Recent purchase notification** fed by real orders through `/wp-json/oyepick/v1/recent-orders`. The interval can be changed in the Customizer (default 25 s). It shows first name and city only.
- Instagram, Facebook, TikTok and WhatsApp links, editable in the Customizer.
- New OyePick logo (SVG) and favicon built in. Upload your own under Customize → Site Identity to override.

## Install on oyepick.com

1. **Back up the site first** (files + database).
2. Download this repo as ZIP. Zip the folders `wordpress/oyepick-theme` and `wordpress/oyepick-store-setup` separately.
3. **Appearance → Themes → Add New → Upload** `oyepick-theme.zip` → Activate.
4. **Plugins → Add New → Upload** `oyepick-store-setup.zip` → Activate → **Tools → OyePick Setup → Run setup**.
   - This sets PKR, Cash on Delivery and reviews, and creates categories, the main menu, About/Contact pages and the 12 products with photos.
   - Products that already exist with the same SKU or name are skipped. Nothing is deleted.
5. **Appearance → Customize → Site Identity**: set the Site Icon to `wordpress/oyepick-theme/assets/images/brand/favicon-512.png`.
6. Before going live:
   - Set prices for **NOHON 22.5W** and **Solar 4-in-1** power banks. They currently show "Price on WhatsApp".
   - Check the 7 existing live-site products to avoid duplicates.
   - Add a note to the Privacy Policy that the purchase notification shows a buyer's first name and city.
