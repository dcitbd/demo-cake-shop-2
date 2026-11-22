#!/usr/bin/env python3
"""
SweetDream Cakes BD — Python Turbo Speed Optimizer & Facebook Shop Feed Generator
Run via cPanel Cron Job or CLI: python3 python_engine/speed_optimizer.py
- Pre-warms JSON storefront cache for ultra-fast load times
- Generates Facebook Shop / Meta Commerce Manager CSV catalog (storage/feeds/fb_catalog.csv)
- Optimizes SQLite3 database (PRAGMA optimize & ANALYZE)
"""
import os
import csv
import json
import sqlite3
from datetime import datetime

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DB_PATH = os.path.join(ROOT, "storage", "cakes_store.sqlite")
CACHE_DIR = os.path.join(ROOT, "storage", "cache")
FEED_DIR = os.path.join(ROOT, "storage", "feeds")
os.makedirs(CACHE_DIR, exist_ok=True)
os.makedirs(FEED_DIR, exist_ok=True)

def run_optimizer(site_url="https://sweetdreamcakesbd.com"):
    if not os.path.exists(DB_PATH):
        print("Database not found:", DB_PATH)
        return
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    cur = conn.cursor()

    # 1. Optimize SQLite indexes
    cur.execute("ANALYZE;")
    cur.execute("PRAGMA optimize;")

    # 2. Export ultra-fast JSON cache for homepage & search
    products = [dict(r) for r in cur.execute("""
        SELECT p.id, p.sku, p.name, p.slug, p.regular_price, p.discount_price, p.discount_percent,
               p.stock, p.rating, p.images_json, c.name AS category_name, b.name AS brand_name
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN brands b ON b.id = p.brand_id
        WHERE p.status = 1
        ORDER BY p.id DESC
    """).fetchall()]

    with open(os.path.join(CACHE_DIR, "products_index.json"), "w", encoding="utf-8") as f:
        json.dump({"updated_at": datetime.now().isoformat(), "count": len(products), "items": products}, f, ensure_ascii=False)

    # 3. Generate Facebook Shop Catalog CSV (Ready to upload to Meta Commerce Manager)
    fb_csv = os.path.join(FEED_DIR, "fb_catalog.csv")
    with open(fb_csv, "w", newline="", encoding="utf-8-sig") as f:
        w = csv.writer(f)
        w.writerow(["id", "title", "description", "availability", "condition", "price", "sale_price", "link", "image_link", "brand", "google_product_category"])
        for p in products:
            imgs = json.loads(p["images_json"] or "[]")
            img_link = f"{site_url}/{imgs[0]}" if imgs else f"{site_url}/assets/images/logo.png"
            w.writerow([
                p["sku"],
                p["name"],
                f"{p['name']} - Freshly baked premium cake from SweetDream Cakes BD.",
                "in stock" if p["stock"] > 0 else "out of stock",
                "new",
                f"{int(p['regular_price'])} BDT",
                f"{int(p['discount_price'])} BDT",
                f"{site_url}/product.php?slug={p['slug']}",
                img_link,
                p["brand_name"] or "SweetDream Cakes BD",
                "Food, Beverages & Tobacco > Food Items > Bakery > Cakes"
            ])

    conn.close()
    print(f"[Turbo Optimizer] Cached {len(products)} cakes & generated {fb_csv}")

if __name__ == "__main__":
    run_optimizer()
