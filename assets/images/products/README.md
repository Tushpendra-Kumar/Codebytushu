# Product Images — Placement Guide

Place all product `.webp` images in this folder:
`/assets/images/products/`

Once images are placed here, update `store/js/data.js` and replace the Unsplash URLs with local paths.

## Required Image Filenames

### T-Shirts
- `tshirt-01.webp` → Developer Mode T-Shirt
- `tshirt-02.webp` → Eat Sleep Code T-Shirt
- `premium-tshirt-01.webp` → Premium Tech T-Shirt
- `oversized-tshirt-01.webp` → Bug Hunter Oversized T-Shirt
- `polo-tshirt-01.webp` → Smart Coder Polo T-Shirt

### Hoodies
- `hoodie-01.webp` → Night Owl Developer Hoodie

### Mugs
- `coffee-mug-01.webp` → Code Fuel Coffee Mug
- `magic-mug-01.webp` → Dark Mode Magic Mug
- `travel-mug-01.webp` → Nomad Developer Travel Mug

### Accessories
- `steel-bottle-01.webp` → Stay Hydrated Steel Bottle
- `water-bottle-01.webp` → Minimalist Water Bottle
- `notebook-01.webp` → Code Architecture Notebook
- `premium-diary-01.webp` → Tech Executive Premium Diary
- `planner-01.webp` → Sprint & Goals Planner
- `backpack-01.webp` → Tech Nomad Backpack
- `laptop-bag-01.webp` → Minimalist Laptop Sleeve
- `mobile-cover-01.webp` → Circuit Board Mobile Cover
- `pop-socket-01.webp` → Terminal Icon Pop Socket
- `phone-stand-01.webp` → Aluminum Phone Stand
- `laptop-stand-01.webp` → Ergonomic Laptop Stand
- `usb-hub-01.webp` → 7-in-1 Type-C USB Hub

### Stickers
- `keyboard-stickers-01.webp` → Shortcut Keys Sticker Pack
- `developer-quote-stickers-01.webp` → Dev Quotes Sticker Pack

### E-books
- `java-notes-pdf-01.webp` → Java Mastery Notes
- `react-notes-pdf-01.webp` → React.js Complete Guide

### Digital Templates
- `resume-templates-01.webp` → ATS-Friendly Resume Templates
- `portfolio-templates-01.webp` → Modern Developer Portfolio Kit

## How to Replace URLs in data.js

After placing images, in `store/js/data.js`:
- Replace `https://images.unsplash.com/...` with `../assets/images/products/FILENAME.webp`
- For product-details page, path will be `../../assets/images/products/FILENAME.webp`
