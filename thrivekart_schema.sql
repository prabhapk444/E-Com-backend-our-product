-- =====================================================
--  THRIVEKART — MULTI-TENANT SAAS E-COMMERCE PLATFORM
--  Database Schema (MySQL 8.0+)
--  Covers: Shops (Tenants), Products, Orders, Payments,
--          Inventory, Shipping, Reviews, Payouts, Analytics
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- =====================================================
-- 1. PLATFORM ADMINISTRATION
-- =====================================================

CREATE TABLE platform_settings (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key     VARCHAR(100) NOT NULL UNIQUE,
    setting_value   TEXT,
    setting_group   VARCHAR(50) DEFAULT 'general',
    is_public       TINYINT(1)  DEFAULT 0,
    created_at      TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP   DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE admin_users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('super_admin','admin','support') DEFAULT 'support',
    is_active       TINYINT(1)  DEFAULT 1,
    last_login_at   TIMESTAMP   NULL,
    created_at      TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP   DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- 2. SUBSCRIPTION PLANS
-- =====================================================

CREATE TABLE subscription_plans (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                    VARCHAR(100)    NOT NULL,
    slug                    VARCHAR(100)    NOT NULL UNIQUE,
    description             TEXT,
    price                   DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    billing_cycle           ENUM('monthly','yearly','lifetime') DEFAULT 'monthly',
    max_products            INT UNSIGNED    DEFAULT 100,
    max_staff               INT UNSIGNED    DEFAULT 2,
    max_storage_gb          DECIMAL(5,2)    DEFAULT 1.00,
    transaction_fee_percent DECIMAL(5,4)    DEFAULT 0.0200,
    features                JSON,
    is_active               TINYINT(1)      DEFAULT 1,
    sort_order              INT             DEFAULT 0,
    created_at              TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- 3. SHOPS (TENANTS)
-- =====================================================

CREATE TABLE shops (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid                CHAR(26)        NOT NULL UNIQUE,
    name                VARCHAR(200)    NOT NULL,
    slug                VARCHAR(200)    NOT NULL UNIQUE,
    tagline             VARCHAR(255),
    description         TEXT,
    logo_url            VARCHAR(500),
    banner_url          VARCHAR(500),
    owner_name          VARCHAR(100)    NOT NULL,
    owner_email         VARCHAR(150)    NOT NULL UNIQUE,
    owner_phone         VARCHAR(20),
    password_hash       VARCHAR(255)    NOT NULL,
    business_type       ENUM('individual','partnership','pvt_ltd','ltd','llp','other') DEFAULT 'individual',
    gstin               VARCHAR(20),
    pan                 VARCHAR(20),
    shop_email          VARCHAR(150),
    shop_phone          VARCHAR(20),
    status              ENUM('pending','active','suspended','closed') DEFAULT 'pending',
    email_verified_at   TIMESTAMP       NULL,
    phone_verified_at   TIMESTAMP       NULL,
    kyc_status          ENUM('not_submitted','pending','approved','rejected') DEFAULT 'not_submitted',
    current_plan_id     INT UNSIGNED,
    plan_expires_at     TIMESTAMP       NULL,
    timezone            VARCHAR(50)     DEFAULT 'Asia/Kolkata',
    currency            CHAR(3)         DEFAULT 'INR',
    language            CHAR(5)         DEFAULT 'en',
    last_login_at       TIMESTAMP       NULL,
    created_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          TIMESTAMP       NULL,
    FOREIGN KEY (current_plan_id) REFERENCES subscription_plans(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Legacy settings table for backward compatibility
CREATE TABLE IF NOT EXISTS settings (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) DEFAULT 'My Store',
    email           VARCHAR(150),
    phone           VARCHAR(20),
    place           VARCHAR(100),
    store_hours     VARCHAR(100),
    store_closed    TINYINT(1) DEFAULT 0,
    low_stock_threshold INT DEFAULT 5,
    currency        VARCHAR(10) DEFAULT 'INR',
    logo_url        VARCHAR(255),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE shop_settings (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id         INT UNSIGNED    NOT NULL,
    setting_key     VARCHAR(100)    NOT NULL,
    setting_value   TEXT,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_shop_setting (shop_id, setting_key),
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE shop_addresses (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id         INT UNSIGNED    NOT NULL,
    address_type    ENUM('registered','warehouse','pickup') DEFAULT 'registered',
    address_line1   VARCHAR(255)    NOT NULL,
    address_line2   VARCHAR(255),
    city            VARCHAR(100)    NOT NULL,
    state           VARCHAR(100)    NOT NULL,
    pincode         VARCHAR(20)     NOT NULL,
    country         CHAR(2)         DEFAULT 'IN',
    is_default      TINYINT(1)      DEFAULT 0,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE shop_subscriptions (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id             INT UNSIGNED    NOT NULL,
    plan_id             INT UNSIGNED    NOT NULL,
    status              ENUM('trial','active','cancelled','expired') DEFAULT 'trial',
    started_at          TIMESTAMP       NOT NULL,
    expires_at          TIMESTAMP       NOT NULL,
    cancelled_at        TIMESTAMP       NULL,
    amount_paid         DECIMAL(10,2)   DEFAULT 0.00,
    payment_reference   VARCHAR(100),
    created_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE shop_kyc_documents (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id         INT UNSIGNED    NOT NULL,
    document_type   ENUM('pan','gstin','aadhaar','business_registration','bank_statement','other') NOT NULL,
    document_number VARCHAR(100),
    file_url        VARCHAR(500)    NOT NULL,
    status          ENUM('pending','approved','rejected') DEFAULT 'pending',
    rejection_reason TEXT,
    reviewed_by     INT UNSIGNED,
    reviewed_at     TIMESTAMP       NULL,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id)       REFERENCES shops(id)       ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by)   REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- 4. SHOP STAFF
-- =====================================================

CREATE TABLE shop_staff (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id         INT UNSIGNED    NOT NULL,
    name            VARCHAR(100)    NOT NULL,
    email           VARCHAR(150)    NOT NULL,
    password_hash   VARCHAR(255)    NOT NULL,
    role            ENUM('manager','inventory','orders','support','marketing') DEFAULT 'orders',
    permissions     JSON,
    is_active       TINYINT(1)      DEFAULT 1,
    last_login_at   TIMESTAMP       NULL,
    invited_at      TIMESTAMP       NULL,
    joined_at       TIMESTAMP       NULL,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_staff_email_shop (shop_id, email),
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 5. CUSTOMERS  (platform-wide, shop-agnostic)
-- =====================================================

CREATE TABLE customers (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid                CHAR(26)        NOT NULL UNIQUE,
    first_name          VARCHAR(100)    NOT NULL,
    last_name           VARCHAR(100),
    email               VARCHAR(150)    NOT NULL UNIQUE,
    phone               VARCHAR(20),
    password_hash       VARCHAR(255),
    avatar_url          VARCHAR(500),
    date_of_birth       DATE,
    gender              ENUM('male','female','other','prefer_not_to_say'),
    is_active           TINYINT(1)      DEFAULT 1,
    email_verified_at   TIMESTAMP       NULL,
    phone_verified_at   TIMESTAMP       NULL,
    last_login_at       TIMESTAMP       NULL,
    auth_provider       ENUM('local','google','facebook','apple') DEFAULT 'local',
    auth_provider_id    VARCHAR(255),
    created_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          TIMESTAMP       NULL
) ENGINE=InnoDB;

CREATE TABLE customer_addresses (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT UNSIGNED    NOT NULL,
    label           VARCHAR(50)     DEFAULT 'Home',
    recipient_name  VARCHAR(150)    NOT NULL,
    phone           VARCHAR(20)     NOT NULL,
    address_line1   VARCHAR(255)    NOT NULL,
    address_line2   VARCHAR(255),
    landmark        VARCHAR(255),
    city            VARCHAR(100)    NOT NULL,
    state           VARCHAR(100)    NOT NULL,
    pincode         VARCHAR(20)     NOT NULL,
    country         CHAR(2)         DEFAULT 'IN',
    is_default      TINYINT(1)      DEFAULT 0,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE customer_sessions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT UNSIGNED    NOT NULL,
    token_hash      VARCHAR(255)    NOT NULL UNIQUE,
    device_info     JSON,
    ip_address      VARCHAR(45),
    expires_at      TIMESTAMP       NOT NULL,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE otp_verifications (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identifier      VARCHAR(150)    NOT NULL,
    identifier_type ENUM('email','phone') NOT NULL,
    otp_hash        VARCHAR(255)    NOT NULL,
    purpose         ENUM('registration','login','password_reset','phone_verify') NOT NULL,
    attempts        INT             DEFAULT 0,
    expires_at      TIMESTAMP       NOT NULL,
    verified_at     TIMESTAMP       NULL,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier (identifier, identifier_type)
) ENGINE=InnoDB;

-- =====================================================
-- 6. CATEGORIES  (per-shop, hierarchical)
-- =====================================================

CREATE TABLE categories (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id         INT UNSIGNED    NOT NULL,
    parent_id       INT UNSIGNED,
    name            VARCHAR(150)    NOT NULL,
    slug            VARCHAR(150)    NOT NULL,
    description     TEXT,
    image_url       VARCHAR(500),
    meta_title      VARCHAR(255),
    meta_description TEXT,
    is_active       TINYINT(1)      DEFAULT 1,
    sort_order      INT             DEFAULT 0,
    level           INT             DEFAULT 0,
    path            VARCHAR(500)    COMMENT 'Materialized path e.g. 1/4/12',
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_category_slug_shop (shop_id, slug),
    FOREIGN KEY (shop_id)   REFERENCES shops(id)      ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- 7. BRANDS
-- =====================================================

CREATE TABLE brands (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id     INT UNSIGNED    NOT NULL,
    name        VARCHAR(150)    NOT NULL,
    slug        VARCHAR(150)    NOT NULL,
    logo_url    VARCHAR(500),
    description TEXT,
    is_active   TINYINT(1)      DEFAULT 1,
    created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_brand_slug_shop (shop_id, slug),
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 8. PRODUCT ATTRIBUTES
-- =====================================================

CREATE TABLE attribute_groups (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id     INT UNSIGNED    NOT NULL,
    name        VARCHAR(100)    NOT NULL,
    sort_order  INT             DEFAULT 0,
    created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE attributes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id         INT UNSIGNED    NOT NULL,
    group_id        INT UNSIGNED,
    name            VARCHAR(100)    NOT NULL,
    slug            VARCHAR(100)    NOT NULL,
    type            ENUM('text','number','select','multi_select','boolean','color','date') DEFAULT 'select',
    is_filterable   TINYINT(1)      DEFAULT 0,
    is_required     TINYINT(1)      DEFAULT 0,
    is_variant      TINYINT(1)      DEFAULT 0,
    sort_order      INT             DEFAULT 0,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id)   REFERENCES shops(id)            ON DELETE CASCADE,
    FOREIGN KEY (group_id)  REFERENCES attribute_groups(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE attribute_options (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attribute_id    INT UNSIGNED    NOT NULL,
    value           VARCHAR(150)    NOT NULL,
    label           VARCHAR(150)    NOT NULL,
    color_hex       CHAR(7),
    sort_order      INT             DEFAULT 0,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attribute_id) REFERENCES attributes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 9. PRODUCTS
-- =====================================================

CREATE TABLE products (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid                CHAR(26)        NOT NULL UNIQUE,
    shop_id             INT UNSIGNED    NOT NULL,
    category_id         INT UNSIGNED,
    brand_id            INT UNSIGNED,
    name                VARCHAR(300)    NOT NULL,
    slug                VARCHAR(300)    NOT NULL,
    sku                 VARCHAR(100),
    short_description   TEXT,
    description         LONGTEXT,
    product_type        ENUM('simple','variable','digital','bundle','service') DEFAULT 'simple',
    status              ENUM('draft','active','archived','out_of_stock') DEFAULT 'draft',
    visibility          ENUM('public','hidden','password') DEFAULT 'public',
    is_featured         TINYINT(1)      DEFAULT 0,
    base_price          DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    selling_price       DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    cost_price          DECIMAL(12,2),
    tax_class           ENUM('none','5','12','18','28') DEFAULT 'none',
    tax_inclusive       TINYINT(1)      DEFAULT 1,
    weight              DECIMAL(8,3),
    weight_unit         ENUM('kg','g','lb','oz') DEFAULT 'kg',
    length              DECIMAL(8,2),
    width               DECIMAL(8,2),
    height              DECIMAL(8,2),
    dimension_unit      ENUM('cm','mm','inch') DEFAULT 'cm',
    meta_title          VARCHAR(255),
    meta_description    TEXT,
    meta_keywords       TEXT,
    published_at        TIMESTAMP       NULL,
    created_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          TIMESTAMP       NULL,
    UNIQUE KEY uq_product_slug_shop (shop_id, slug),
    UNIQUE KEY uq_product_sku_shop  (shop_id, sku),
    FOREIGN KEY (shop_id)     REFERENCES shops(id)      ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (brand_id)    REFERENCES brands(id)     ON DELETE SET NULL,
    INDEX idx_status   (status),
    INDEX idx_featured (is_featured),
    FULLTEXT idx_search (name, short_description)
) ENGINE=InnoDB;

CREATE TABLE product_images (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id  INT UNSIGNED    NOT NULL,
    variant_id  INT UNSIGNED,
    url         VARCHAR(500)    NOT NULL,
    alt_text    VARCHAR(255),
    sort_order  INT             DEFAULT 0,
    is_primary  TINYINT(1)      DEFAULT 0,
    created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE product_attribute_values (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id              INT UNSIGNED    NOT NULL,
    attribute_id            INT UNSIGNED    NOT NULL,
    attribute_option_id     INT UNSIGNED,
    custom_value            VARCHAR(255),
    UNIQUE KEY uq_prod_attr (product_id, attribute_id),
    FOREIGN KEY (product_id)          REFERENCES products(id)          ON DELETE CASCADE,
    FOREIGN KEY (attribute_id)        REFERENCES attributes(id)        ON DELETE CASCADE,
    FOREIGN KEY (attribute_option_id) REFERENCES attribute_options(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE product_tags (
    id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED    NOT NULL,
    name    VARCHAR(100)    NOT NULL,
    slug    VARCHAR(100)    NOT NULL,
    UNIQUE KEY uq_tag_slug_shop (shop_id, slug),
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE product_tag_map (
    product_id  INT UNSIGNED NOT NULL,
    tag_id      INT UNSIGNED NOT NULL,
    PRIMARY KEY (product_id, tag_id),
    FOREIGN KEY (product_id) REFERENCES products(id)     ON DELETE CASCADE,
    FOREIGN KEY (tag_id)     REFERENCES product_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 10. PRODUCT VARIANTS
-- =====================================================

CREATE TABLE product_variants (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id  INT UNSIGNED    NOT NULL,
    sku         VARCHAR(100)    NOT NULL,
    name        VARCHAR(255),
    price       DECIMAL(12,2)   NOT NULL,
    cost_price  DECIMAL(12,2),
    is_default  TINYINT(1)      DEFAULT 0,
    is_active   TINYINT(1)      DEFAULT 1,
    weight      DECIMAL(8,3),
    created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE variant_attribute_values (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    variant_id          INT UNSIGNED    NOT NULL,
    attribute_id        INT UNSIGNED    NOT NULL,
    attribute_option_id INT UNSIGNED    NOT NULL,
    UNIQUE KEY uq_variant_attr (variant_id, attribute_id),
    FOREIGN KEY (variant_id)          REFERENCES product_variants(id)  ON DELETE CASCADE,
    FOREIGN KEY (attribute_id)        REFERENCES attributes(id)         ON DELETE CASCADE,
    FOREIGN KEY (attribute_option_id) REFERENCES attribute_options(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 11. INVENTORY
-- =====================================================

CREATE TABLE warehouses (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id     INT UNSIGNED    NOT NULL,
    name        VARCHAR(150)    NOT NULL,
    address_id  INT UNSIGNED,
    is_active   TINYINT(1)      DEFAULT 1,
    created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE inventory (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id             INT UNSIGNED    NOT NULL,
    product_id          INT UNSIGNED    NOT NULL,
    variant_id          INT UNSIGNED,
    warehouse_id        INT UNSIGNED,
    quantity            INT             NOT NULL DEFAULT 0,
    reserved_quantity   INT             NOT NULL DEFAULT 0,
    reorder_level       INT             DEFAULT 0,
    reorder_quantity    INT             DEFAULT 0,
    updated_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_inventory (product_id, variant_id, warehouse_id),
    FOREIGN KEY (shop_id)      REFERENCES shops(id)            ON DELETE CASCADE,
    FOREIGN KEY (product_id)   REFERENCES products(id)         ON DELETE CASCADE,
    FOREIGN KEY (variant_id)   REFERENCES product_variants(id) ON DELETE CASCADE,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)       ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE inventory_transactions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inventory_id    INT UNSIGNED    NOT NULL,
    type            ENUM('purchase','sale','return','adjustment','damage','transfer_in','transfer_out') NOT NULL,
    quantity_change INT             NOT NULL,
    quantity_before INT             NOT NULL,
    quantity_after  INT             NOT NULL,
    reference_type  VARCHAR(50),
    reference_id    INT UNSIGNED,
    notes           TEXT,
    created_by_type ENUM('shop','admin','system') DEFAULT 'system',
    created_by_id   INT UNSIGNED,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 12. PRICING & DISCOUNTS
-- =====================================================

CREATE TABLE coupons (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id             INT UNSIGNED    NOT NULL,
    code                VARCHAR(50)     NOT NULL,
    type                ENUM('percent','fixed','free_shipping','buy_x_get_y') NOT NULL,
    value               DECIMAL(12,2)   NOT NULL,
    min_order_amount    DECIMAL(12,2)   DEFAULT 0.00,
    max_discount_amount DECIMAL(12,2),
    usage_limit         INT UNSIGNED,
    usage_per_customer  INT UNSIGNED    DEFAULT 1,
    used_count          INT UNSIGNED    DEFAULT 0,
    applicable_to       ENUM('all','specific_products','specific_categories') DEFAULT 'all',
    applicable_ids      JSON,
    starts_at           TIMESTAMP       NULL,
    expires_at          TIMESTAMP       NULL,
    is_active           TINYINT(1)      DEFAULT 1,
    created_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_coupon_code_shop (shop_id, code),
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE product_discounts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id         INT UNSIGNED    NOT NULL,
    product_id      INT UNSIGNED,
    variant_id      INT UNSIGNED,
    category_id     INT UNSIGNED,
    discount_type   ENUM('percent','fixed') NOT NULL,
    discount_value  DECIMAL(12,2)   NOT NULL,
    starts_at       TIMESTAMP       NOT NULL,
    expires_at      TIMESTAMP       NOT NULL,
    is_active       TINYINT(1)      DEFAULT 1,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id)    REFERENCES shops(id)            ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)         ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 13. SHIPPING ZONES & RATES
-- =====================================================

CREATE TABLE shipping_zones (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id     INT UNSIGNED    NOT NULL,
    name        VARCHAR(100)    NOT NULL,
    countries   JSON,
    states      JSON,
    pincodes    JSON,
    created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE shipping_rates (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    zone_id             INT UNSIGNED    NOT NULL,
    name                VARCHAR(100)    NOT NULL,
    method              ENUM('flat','weight_based','order_based','free') DEFAULT 'flat',
    price               DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    min_weight          DECIMAL(8,3),
    max_weight          DECIMAL(8,3),
    min_order_amount    DECIMAL(12,2),
    max_order_amount    DECIMAL(12,2),
    estimated_days_min  INT,
    estimated_days_max  INT,
    is_active           TINYINT(1)      DEFAULT 1,
    created_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (zone_id) REFERENCES shipping_zones(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 14. SHOPPING CART
-- =====================================================

CREATE TABLE carts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid            CHAR(26)        NOT NULL UNIQUE,
    shop_id         INT UNSIGNED    NOT NULL,
    customer_id     INT UNSIGNED,
    session_token   VARCHAR(100),
    coupon_id       INT UNSIGNED,
    notes           TEXT,
    expires_at      TIMESTAMP,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id)     REFERENCES shops(id)     ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (coupon_id)   REFERENCES coupons(id)   ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE cart_items (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cart_id     INT UNSIGNED    NOT NULL,
    product_id  INT UNSIGNED    NOT NULL,
    variant_id  INT UNSIGNED,
    quantity    INT UNSIGNED    NOT NULL DEFAULT 1,
    unit_price  DECIMAL(12,2)   NOT NULL,
    created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cart_item (cart_id, product_id, variant_id),
    FOREIGN KEY (cart_id)    REFERENCES carts(id)            ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)         ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- 15. ORDERS
-- =====================================================

CREATE TABLE orders (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid                CHAR(26)        NOT NULL UNIQUE,
    order_number        VARCHAR(30)     NOT NULL UNIQUE,
    shop_id             INT UNSIGNED    NOT NULL,
    customer_id         INT UNSIGNED    NOT NULL,
    status              ENUM('pending','confirmed','processing','shipped','delivered','cancelled','refunded','failed') DEFAULT 'pending',
    payment_status      ENUM('unpaid','partially_paid','paid','refunded','failed') DEFAULT 'unpaid',
    fulfillment_status  ENUM('unfulfilled','partial','fulfilled','returned') DEFAULT 'unfulfilled',
    subtotal            DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    discount_amount     DECIMAL(12,2)   DEFAULT 0.00,
    shipping_amount     DECIMAL(12,2)   DEFAULT 0.00,
    tax_amount          DECIMAL(12,2)   DEFAULT 0.00,
    total_amount        DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    coupon_id           INT UNSIGNED,
    coupon_code         VARCHAR(50),
    shipping_address    JSON            NOT NULL,
    billing_address     JSON,
    shipping_method     VARCHAR(100),
    shipping_zone_id    INT UNSIGNED,
    customer_notes      TEXT,
    admin_notes         TEXT,
    confirmed_at        TIMESTAMP       NULL,
    shipped_at          TIMESTAMP       NULL,
    delivered_at        TIMESTAMP       NULL,
    cancelled_at        TIMESTAMP       NULL,
    created_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id)     REFERENCES shops(id)     ON DELETE RESTRICT,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (coupon_id)   REFERENCES coupons(id)   ON DELETE SET NULL,
    INDEX idx_order_status   (shop_id, status),
    INDEX idx_order_customer (customer_id)
) ENGINE=InnoDB;

CREATE TABLE order_items (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id            INT UNSIGNED    NOT NULL,
    product_id          INT UNSIGNED    NOT NULL,
    variant_id          INT UNSIGNED,
    product_name        VARCHAR(300)    NOT NULL,
    variant_name        VARCHAR(255),
    sku                 VARCHAR(100),
    quantity            INT UNSIGNED    NOT NULL,
    unit_price          DECIMAL(12,2)   NOT NULL,
    discount_amount     DECIMAL(12,2)   DEFAULT 0.00,
    tax_amount          DECIMAL(12,2)   DEFAULT 0.00,
    total_price         DECIMAL(12,2)   NOT NULL,
    product_snapshot    JSON,
    fulfillment_status  ENUM('pending','packed','shipped','delivered','returned') DEFAULT 'pending',
    created_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id)   REFERENCES orders(id)           ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)         ON DELETE RESTRICT,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE order_status_history (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        INT UNSIGNED    NOT NULL,
    from_status     VARCHAR(50),
    to_status       VARCHAR(50)     NOT NULL,
    notes           TEXT,
    changed_by_type ENUM('customer','shop','admin','system') DEFAULT 'system',
    changed_by_id   INT UNSIGNED,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 16. PAYMENTS
-- =====================================================

CREATE TABLE payment_gateways (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id     INT UNSIGNED,
    name        VARCHAR(100)    NOT NULL,
    provider    ENUM('razorpay','paytm','stripe','phonepe','cashfree','cod','upi','bank_transfer') NOT NULL,
    credentials JSON,
    is_active   TINYINT(1)      DEFAULT 1,
    is_test_mode TINYINT(1)     DEFAULT 0,
    sort_order  INT             DEFAULT 0,
    created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE payments (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid                CHAR(26)        NOT NULL UNIQUE,
    order_id            INT UNSIGNED    NOT NULL,
    shop_id             INT UNSIGNED    NOT NULL,
    customer_id         INT UNSIGNED    NOT NULL,
    gateway_id          INT UNSIGNED,
    amount              DECIMAL(12,2)   NOT NULL,
    currency            CHAR(3)         DEFAULT 'INR',
    status              ENUM('initiated','pending','success','failed','refunded','cancelled') DEFAULT 'initiated',
    gateway_order_id    VARCHAR(255),
    gateway_payment_id  VARCHAR(255),
    gateway_signature   VARCHAR(500),
    payment_method      VARCHAR(50),
    failure_reason      TEXT,
    metadata            JSON,
    paid_at             TIMESTAMP       NULL,
    created_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id)    REFERENCES orders(id)    ON DELETE RESTRICT,
    FOREIGN KEY (shop_id)     REFERENCES shops(id)     ON DELETE RESTRICT,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE refunds (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid                CHAR(26)        NOT NULL UNIQUE,
    order_id            INT UNSIGNED    NOT NULL,
    payment_id          INT UNSIGNED    NOT NULL,
    amount              DECIMAL(12,2)   NOT NULL,
    reason              TEXT,
    status              ENUM('pending','processing','completed','failed') DEFAULT 'pending',
    gateway_refund_id   VARCHAR(255),
    processed_at        TIMESTAMP       NULL,
    created_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE RESTRICT,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================================================
-- 17. SHIPMENTS & TRACKING
-- =====================================================

CREATE TABLE shipments (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid                    CHAR(26)        NOT NULL UNIQUE,
    order_id                INT UNSIGNED    NOT NULL,
    shop_id                 INT UNSIGNED    NOT NULL,
    courier_name            VARCHAR(100),
    tracking_number         VARCHAR(150),
    tracking_url            VARCHAR(500),
    status                  ENUM('pending','packed','in_transit','out_for_delivery','delivered','returned','failed') DEFAULT 'pending',
    estimated_delivery_date DATE,
    shipped_at              TIMESTAMP       NULL,
    delivered_at            TIMESTAMP       NULL,
    created_at              TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT,
    FOREIGN KEY (shop_id)  REFERENCES shops(id)  ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE shipment_items (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shipment_id     INT UNSIGNED    NOT NULL,
    order_item_id   INT UNSIGNED    NOT NULL,
    quantity        INT UNSIGNED    NOT NULL,
    FOREIGN KEY (shipment_id)   REFERENCES shipments(id)    ON DELETE CASCADE,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE shipment_tracking_events (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shipment_id INT UNSIGNED    NOT NULL,
    status      VARCHAR(100)    NOT NULL,
    description TEXT,
    location    VARCHAR(255),
    event_time  TIMESTAMP       NOT NULL,
    created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 18. RETURNS & REFUND REQUESTS
-- =====================================================

CREATE TABLE return_requests (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid                CHAR(26)        NOT NULL UNIQUE,
    order_id            INT UNSIGNED    NOT NULL,
    customer_id         INT UNSIGNED    NOT NULL,
    reason              ENUM('damaged','wrong_item','not_as_described','defective','size_issue','changed_mind','other') NOT NULL,
    reason_details      TEXT,
    status              ENUM('requested','approved','rejected','pickup_scheduled','picked_up','inspected','refund_initiated','completed') DEFAULT 'requested',
    refund_method       ENUM('original_payment','store_credit','bank_transfer') DEFAULT 'original_payment',
    refund_amount       DECIMAL(12,2),
    rejection_reason    TEXT,
    reviewed_by_id      INT UNSIGNED,
    reviewed_at         TIMESTAMP       NULL,
    created_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id)    REFERENCES orders(id)    ON DELETE RESTRICT,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE return_request_items (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    return_request_id   INT UNSIGNED    NOT NULL,
    order_item_id       INT UNSIGNED    NOT NULL,
    quantity            INT UNSIGNED    NOT NULL,
    item_condition      ENUM('unopened','opened','damaged') DEFAULT 'opened',
    FOREIGN KEY (return_request_id) REFERENCES return_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (order_item_id)     REFERENCES order_items(id)     ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 19. REVIEWS & RATINGS
-- =====================================================

CREATE TABLE product_reviews (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id             INT UNSIGNED    NOT NULL,
    product_id          INT UNSIGNED    NOT NULL,
    customer_id         INT UNSIGNED    NOT NULL,
    order_item_id       INT UNSIGNED,
    rating              TINYINT         NOT NULL CHECK (rating BETWEEN 1 AND 5),
    title               VARCHAR(255),
    body                TEXT,
    is_verified_purchase TINYINT(1)     DEFAULT 0,
    status              ENUM('pending','approved','rejected') DEFAULT 'pending',
    helpful_count       INT             DEFAULT 0,
    images              JSON,
    created_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_review_customer_product (customer_id, product_id),
    FOREIGN KEY (shop_id)     REFERENCES shops(id)       ON DELETE CASCADE,
    FOREIGN KEY (product_id)  REFERENCES products(id)    ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id)   ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE review_responses (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    review_id       INT UNSIGNED    NOT NULL UNIQUE,
    shop_id         INT UNSIGNED    NOT NULL,
    response        TEXT            NOT NULL,
    responded_by_id INT UNSIGNED,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES product_reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id)   REFERENCES shops(id)            ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 20. WISHLIST
-- =====================================================

CREATE TABLE wishlists (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED    NOT NULL,
    shop_id     INT UNSIGNED    NOT NULL,
    product_id  INT UNSIGNED    NOT NULL,
    variant_id  INT UNSIGNED,
    created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_wishlist (customer_id, shop_id, product_id, variant_id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id)     REFERENCES shops(id)     ON DELETE CASCADE,
    FOREIGN KEY (product_id)  REFERENCES products(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 21. NOTIFICATIONS
-- =====================================================

CREATE TABLE notification_templates (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id     INT UNSIGNED,
    event_type  VARCHAR(100)    NOT NULL,
    channel     ENUM('email','sms','push','whatsapp') NOT NULL,
    subject     VARCHAR(255),
    body        TEXT            NOT NULL,
    variables   JSON,
    is_active   TINYINT(1)      DEFAULT 1,
    created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id         INT UNSIGNED,
    recipient_type  ENUM('customer','shop_owner','admin') NOT NULL,
    recipient_id    INT UNSIGNED    NOT NULL,
    channel         ENUM('email','sms','push','whatsapp','in_app') NOT NULL,
    subject         VARCHAR(255),
    body            TEXT            NOT NULL,
    reference_type  VARCHAR(50),
    reference_id    INT UNSIGNED,
    status          ENUM('pending','sent','failed','read') DEFAULT 'pending',
    sent_at         TIMESTAMP       NULL,
    read_at         TIMESTAMP       NULL,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE SET NULL,
    INDEX idx_recipient (recipient_type, recipient_id),
    INDEX idx_notif_status (status)
) ENGINE=InnoDB;

-- =====================================================
-- 22. SHOP PAYOUTS & WALLET
-- =====================================================

CREATE TABLE shop_bank_accounts (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id             INT UNSIGNED    NOT NULL,
    bank_name           VARCHAR(100)    NOT NULL,
    account_holder_name VARCHAR(150)    NOT NULL,
    account_number      VARCHAR(30)     NOT NULL,
    ifsc_code           VARCHAR(20)     NOT NULL,
    account_type        ENUM('savings','current') DEFAULT 'current',
    is_verified         TINYINT(1)      DEFAULT 0,
    is_default          TINYINT(1)      DEFAULT 0,
    created_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE shop_wallet (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id         INT UNSIGNED    NOT NULL UNIQUE,
    balance         DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    pending_balance DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE shop_wallet_transactions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id         INT UNSIGNED    NOT NULL,
    type            ENUM('credit','debit') NOT NULL,
    amount          DECIMAL(12,2)   NOT NULL,
    balance_before  DECIMAL(12,2)   NOT NULL,
    balance_after   DECIMAL(12,2)   NOT NULL,
    reference_type  ENUM('order','refund','payout','platform_fee','adjustment') NOT NULL,
    reference_id    INT UNSIGNED,
    description     TEXT,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE shop_payouts (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid                CHAR(26)        NOT NULL UNIQUE,
    shop_id             INT UNSIGNED    NOT NULL,
    bank_account_id     INT UNSIGNED    NOT NULL,
    amount              DECIMAL(12,2)   NOT NULL,
    platform_fee        DECIMAL(12,2)   DEFAULT 0.00,
    net_amount          DECIMAL(12,2)   NOT NULL,
    status              ENUM('pending','processing','completed','failed') DEFAULT 'pending',
    reference_number    VARCHAR(100),
    payout_period_start DATE,
    payout_period_end   DATE,
    processed_at        TIMESTAMP       NULL,
    failure_reason      TEXT,
    created_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id)         REFERENCES shops(id)             ON DELETE RESTRICT,
    FOREIGN KEY (bank_account_id) REFERENCES shop_bank_accounts(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================================================
-- 23. SEO, PAGES & BANNERS
-- =====================================================

CREATE TABLE seo_metadata (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id         INT UNSIGNED    NOT NULL,
    entity_type     ENUM('product','category','page','brand') NOT NULL,
    entity_id       INT UNSIGNED    NOT NULL,
    meta_title      VARCHAR(255),
    meta_description TEXT,
    meta_keywords   TEXT,
    og_title        VARCHAR(255),
    og_description  TEXT,
    og_image_url    VARCHAR(500),
    canonical_url   VARCHAR(500),
    schema_markup   JSON,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_seo (shop_id, entity_type, entity_id),
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pages (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id         INT UNSIGNED    NOT NULL,
    title           VARCHAR(255)    NOT NULL,
    slug            VARCHAR(255)    NOT NULL,
    content         LONGTEXT,
    is_published    TINYINT(1)      DEFAULT 0,
    published_at    TIMESTAMP       NULL,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_page_slug_shop (shop_id, slug),
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE banners (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id         INT UNSIGNED    NOT NULL,
    title           VARCHAR(255)    NOT NULL,
    subtitle        VARCHAR(255),
    image_url       VARCHAR(500)    NOT NULL,
    mobile_image_url VARCHAR(500),
    link_url        VARCHAR(500),
    position        ENUM('home_hero','home_mid','sidebar','category_top') DEFAULT 'home_hero',
    is_active       TINYINT(1)      DEFAULT 1,
    starts_at       TIMESTAMP       NULL,
    expires_at      TIMESTAMP       NULL,
    sort_order      INT             DEFAULT 0,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 24. ANALYTICS
-- =====================================================

CREATE TABLE product_views (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id     INT UNSIGNED    NOT NULL,
    product_id  INT UNSIGNED    NOT NULL,
    customer_id INT UNSIGNED,
    session_id  VARCHAR(100),
    ip_address  VARCHAR(45),
    referrer    VARCHAR(500),
    viewed_at   TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id)    REFERENCES shops(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_viewed_at (viewed_at)
) ENGINE=InnoDB;

CREATE TABLE shop_daily_stats (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id         INT UNSIGNED    NOT NULL,
    stat_date       DATE            NOT NULL,
    total_visitors  INT             DEFAULT 0,
    unique_visitors INT             DEFAULT 0,
    total_orders    INT             DEFAULT 0,
    total_revenue   DECIMAL(12,2)   DEFAULT 0.00,
    total_items_sold INT            DEFAULT 0,
    avg_order_value DECIMAL(12,2)   DEFAULT 0.00,
    cancelled_orders INT            DEFAULT 0,
    refunded_orders INT             DEFAULT 0,
    new_customers   INT             DEFAULT 0,
    UNIQUE KEY uq_shop_date (shop_id, stat_date),
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 25. AUDIT LOG
-- =====================================================

CREATE TABLE audit_logs (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id     INT UNSIGNED,
    actor_type  ENUM('customer','shop','shop_staff','admin','system') NOT NULL,
    actor_id    INT UNSIGNED,
    action      VARCHAR(100)    NOT NULL,
    entity_type VARCHAR(100),
    entity_id   INT UNSIGNED,
    old_values  JSON,
    new_values  JSON,
    ip_address  VARCHAR(45),
    user_agent  TEXT,
    created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_actor    (actor_type, actor_id),
    INDEX idx_entity   (entity_type, entity_id),
    INDEX idx_audit_ts (created_at)
) ENGINE=InnoDB;

-- =====================================================
-- TRIGGERS
-- =====================================================

DELIMITER $$

CREATE TRIGGER trg_generate_order_number
BEFORE INSERT ON orders
FOR EACH ROW
BEGIN
    DECLARE v_prefix    VARCHAR(10);
    DECLARE v_count     INT;
    SELECT UPPER(LEFT(REPLACE(slug, '-', ''), 4))
      INTO v_prefix
      FROM shops WHERE id = NEW.shop_id;
    SELECT COUNT(*) + 1
      INTO v_count
      FROM orders WHERE shop_id = NEW.shop_id;
    SET NEW.order_number = CONCAT(
        IFNULL(v_prefix, 'ORD'), '-',
        DATE_FORMAT(NOW(), '%y%m%d'), '-',
        LPAD(v_count, 5, '0')
    );
END$$

CREATE TRIGGER trg_check_shop_active_before_order
BEFORE INSERT ON orders
FOR EACH ROW
BEGIN
    DECLARE v_status VARCHAR(20);
    SELECT status INTO v_status FROM shops WHERE id = NEW.shop_id;
    IF v_status != 'active' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot place order: shop is not active.';
    END IF;
END$$

CREATE TRIGGER trg_reserve_inventory_on_order_item
AFTER INSERT ON order_items
FOR EACH ROW
BEGIN
    DECLARE v_inv_id        INT UNSIGNED;
    DECLARE v_qty_before    INT;

    SELECT id, quantity INTO v_inv_id, v_qty_before
      FROM inventory
     WHERE product_id = NEW.product_id
       AND (variant_id = NEW.variant_id OR (variant_id IS NULL AND NEW.variant_id IS NULL))
     LIMIT 1;

    IF v_inv_id IS NOT NULL THEN
        UPDATE inventory
           SET reserved_quantity = reserved_quantity + NEW.quantity
         WHERE id = v_inv_id;

        INSERT INTO inventory_transactions
            (inventory_id, type, quantity_change, quantity_before, quantity_after, reference_type, reference_id, created_by_type)
        VALUES
            (v_inv_id, 'sale', -NEW.quantity, v_qty_before, v_qty_before - NEW.quantity, 'order', NEW.order_id, 'system');
    END IF;
END$$

CREATE TRIGGER trg_set_order_timestamps
BEFORE UPDATE ON orders
FOR EACH ROW
BEGIN
    IF NEW.status = 'confirmed'  AND OLD.status != 'confirmed'  THEN SET NEW.confirmed_at  = CURRENT_TIMESTAMP; END IF;
    IF NEW.status = 'shipped'    AND OLD.status != 'shipped'    THEN SET NEW.shipped_at    = CURRENT_TIMESTAMP; END IF;
    IF NEW.status = 'delivered'  AND OLD.status != 'delivered'  THEN SET NEW.delivered_at  = CURRENT_TIMESTAMP; END IF;
    IF NEW.status = 'cancelled'  AND OLD.status != 'cancelled'  THEN SET NEW.cancelled_at  = CURRENT_TIMESTAMP; END IF;
END$$

CREATE TRIGGER trg_log_order_status_change
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    IF NEW.status != OLD.status THEN
        INSERT INTO order_status_history (order_id, from_status, to_status, changed_by_type)
        VALUES (NEW.id, OLD.status, NEW.status, 'system');
    END IF;
END$$

CREATE TRIGGER trg_release_inventory_on_cancel
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    IF NEW.status = 'cancelled' AND OLD.status != 'cancelled' THEN
        UPDATE inventory i
          JOIN order_items oi
            ON oi.order_id = NEW.id
           AND i.product_id = oi.product_id
           AND (i.variant_id = oi.variant_id OR (i.variant_id IS NULL AND oi.variant_id IS NULL))
           SET i.quantity          = i.quantity + oi.quantity,
               i.reserved_quantity = GREATEST(0, i.reserved_quantity - oi.quantity);
    END IF;
END$$

CREATE TRIGGER trg_deduct_reserved_on_delivery
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    IF NEW.status = 'delivered' AND OLD.status != 'delivered' THEN
        UPDATE inventory i
          JOIN order_items oi
            ON oi.order_id = NEW.id
           AND i.product_id = oi.product_id
           AND (i.variant_id = oi.variant_id OR (i.variant_id IS NULL AND oi.variant_id IS NULL))
           SET i.reserved_quantity = GREATEST(0, i.reserved_quantity - oi.quantity);
    END IF;
END$$

CREATE TRIGGER trg_update_product_status_on_inventory_change
AFTER UPDATE ON inventory
FOR EACH ROW
BEGIN
    DECLARE v_total INT;
    IF NEW.quantity != OLD.quantity THEN
        SELECT SUM(quantity) INTO v_total
          FROM inventory WHERE product_id = NEW.product_id;

        IF v_total = 0 THEN
            UPDATE products SET status = 'out_of_stock'
             WHERE id = NEW.product_id AND status = 'active';
        ELSEIF v_total > 0 THEN
            UPDATE products SET status = 'active'
             WHERE id = NEW.product_id AND status = 'out_of_stock';
        END IF;
    END IF;
END$$

CREATE TRIGGER trg_credit_shop_wallet_on_payment
AFTER UPDATE ON payments
FOR EACH ROW
BEGIN
    DECLARE v_fee_pct       DECIMAL(5,4) DEFAULT 0.0200;
    DECLARE v_platform_fee  DECIMAL(12,2);
    DECLARE v_net           DECIMAL(12,2);
    DECLARE v_bal_before    DECIMAL(12,2);

    IF NEW.status = 'success' AND OLD.status != 'success' THEN
        SELECT COALESCE(sp.transaction_fee_percent, 0.0200)
          INTO v_fee_pct
          FROM shops s
          JOIN subscription_plans sp ON sp.id = s.current_plan_id
         WHERE s.id = NEW.shop_id;

        SET v_platform_fee = ROUND(NEW.amount * v_fee_pct, 2);
        SET v_net          = NEW.amount - v_platform_fee;

        INSERT INTO shop_wallet (shop_id, balance, pending_balance)
             VALUES (NEW.shop_id, v_net, 0)
        ON DUPLICATE KEY UPDATE
            balance = balance + v_net;

        SELECT balance - v_net INTO v_bal_before
          FROM shop_wallet WHERE shop_id = NEW.shop_id;

        INSERT INTO shop_wallet_transactions
            (shop_id, type, amount, balance_before, balance_after, reference_type, reference_id, description)
        VALUES
            (NEW.shop_id, 'credit', v_net, v_bal_before, v_bal_before + v_net,
             'order', NEW.order_id, CONCAT('Payment received for order #', NEW.order_id));

        UPDATE orders SET payment_status = 'paid' WHERE id = NEW.order_id;
    END IF;
END$$

CREATE TRIGGER trg_increment_coupon_usage
AFTER INSERT ON orders
FOR EACH ROW
BEGIN
    IF NEW.coupon_id IS NOT NULL THEN
        UPDATE coupons SET used_count = used_count + 1 WHERE id = NEW.coupon_id;
    END IF;
END$$

CREATE TRIGGER trg_init_shop_wallet
AFTER INSERT ON shops
FOR EACH ROW
BEGIN
    INSERT INTO shop_wallet (shop_id, balance, pending_balance)
    VALUES (NEW.id, 0.00, 0.00);
END$$

CREATE TRIGGER trg_activate_shop_subscription
AFTER UPDATE ON shop_subscriptions
FOR EACH ROW
BEGIN
    IF NEW.status = 'active' AND OLD.status != 'active' THEN
        UPDATE shops
           SET current_plan_id = NEW.plan_id,
               plan_expires_at = NEW.expires_at
         WHERE id = NEW.shop_id;
    END IF;
END$$

CREATE TRIGGER trg_audit_shop_status_change
AFTER UPDATE ON shops
FOR EACH ROW
BEGIN
    IF NEW.status != OLD.status THEN
        INSERT INTO audit_logs (shop_id, actor_type, action, entity_type, entity_id, old_values, new_values)
        VALUES (NEW.id, 'system', 'status_changed', 'shop', NEW.id,
                JSON_OBJECT('status', OLD.status),
                JSON_OBJECT('status', NEW.status));
    END IF;
END$$

CREATE TRIGGER trg_update_daily_stats_on_order
AFTER INSERT ON orders
FOR EACH ROW
BEGIN
    INSERT INTO shop_daily_stats (shop_id, stat_date, total_orders, total_revenue, avg_order_value)
         VALUES (NEW.shop_id, CURDATE(), 1, NEW.total_amount, NEW.total_amount)
    ON DUPLICATE KEY UPDATE
        total_orders    = total_orders + 1,
        total_revenue   = total_revenue + NEW.total_amount,
        avg_order_value = (total_revenue + NEW.total_amount) / (total_orders + 1);
END$$

CREATE TRIGGER trg_update_daily_stats_on_cancel
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    IF NEW.status = 'cancelled' AND OLD.status != 'cancelled' THEN
        UPDATE shop_daily_stats
           SET cancelled_orders = cancelled_orders + 1
         WHERE shop_id = NEW.shop_id AND stat_date = DATE(NEW.created_at);
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- ADDITIONAL PERFORMANCE INDEXES
-- =====================================================

CREATE INDEX idx_products_shop_status      ON products(shop_id, status);
CREATE INDEX idx_products_category         ON products(category_id);
CREATE INDEX idx_products_featured         ON products(shop_id, is_featured);
CREATE INDEX idx_orders_shop_created       ON orders(shop_id, created_at);
CREATE INDEX idx_orders_customer_created   ON orders(customer_id, created_at);
CREATE INDEX idx_order_items_order         ON order_items(order_id);
CREATE INDEX idx_payments_order            ON payments(order_id);
CREATE INDEX idx_payments_status           ON payments(status);
CREATE INDEX idx_inventory_product_variant ON inventory(product_id, variant_id);
CREATE INDEX idx_shipments_order           ON shipments(order_id);
CREATE INDEX idx_reviews_product_status    ON product_reviews(product_id, status);
CREATE INDEX idx_product_views_shop_date   ON product_views(shop_id, viewed_at);
CREATE INDEX idx_notifications_recipient   ON notifications(recipient_type, recipient_id, status);
CREATE INDEX idx_cart_session              ON carts(session_token);
CREATE INDEX idx_coupon_code               ON coupons(shop_id, code, is_active);

-- =====================================================
-- SEED DATA
-- =====================================================

INSERT INTO subscription_plans (name, slug, description, price, billing_cycle, max_products, max_staff, transaction_fee_percent, features) VALUES
('Free Starter',  'free-starter', 'Perfect to get started', 0.00, 'monthly', 50, 1, 0.0500,
 '["Basic storefront","Up to 50 products","COD payments only","Email support"]'),
('Growth', 'growth', 'For businesses ready to scale', 999.00, 'monthly', 500, 5, 0.0200,
 '["Custom domain","Up to 500 products","All payment gateways","Analytics dashboard","Discount coupons","Priority support"]'),
('Pro', 'pro', 'For established stores', 2999.00, 'monthly', 5000, 15, 0.0100,
 '["Everything in Growth","Up to 5000 products","API access","Advanced analytics","Abandoned cart recovery","Multi-warehouse","Dedicated support"]'),
('Enterprise', 'enterprise', 'Unlimited scale, white-glove service', 9999.00, 'monthly', 999999, 999, 0.0050,
 '["Unlimited products","Unlimited staff","Custom integrations","SLA guarantee","Dedicated account manager","White-label option"]');

INSERT INTO platform_settings (setting_key, setting_value, setting_group, is_public) VALUES
('platform_name',          'ThriveKart',                'general',      1),
('platform_tagline',       'Grow Your Business Online', 'general',      1),
('platform_currency',      'INR',                       'general',      1),
('platform_country',       'IN',                        'general',      1),
('platform_timezone',      'Asia/Kolkata',              'general',      0),
('support_email',          'support@thrivekart.com',    'contact',      1),
('min_payout_amount',      '500',                       'payout',       0),
('payout_cycle_days',      '7',                         'payout',       0),
('trial_days',             '14',                        'subscription', 0),
('max_product_images',     '10',                        'products',     1),
('max_review_images',      '5',                         'reviews',      1),
('order_auto_cancel_hours','24',                         'orders',       0);

-- Seed SuperAdmin user (password: admin123)
INSERT INTO admin_users (name, email, password_hash, role) VALUES
('Super Admin', 'admin@thrivekart.com', '$2y$10$abcdefghijklmnopqrstuvwxALGyYqhZ5vKxK0hGxK0hGxK0hGxK', 'super_admin');

-- Seed default settings
INSERT INTO settings (name, email, low_stock_threshold) VALUES
('My Store', 'store@example.com', 5);

SET FOREIGN_KEY_CHECKS = 1;