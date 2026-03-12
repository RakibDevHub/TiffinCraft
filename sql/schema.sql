-- =============================================
-- 1. Sequences
-- =============================================
CREATE SEQUENCE user_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE kitchen_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE category_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE tag_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE menu_item_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE area_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE order_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE plan_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE subscription_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE payment_transactions_seq START WITH 1 INCREMENT BY 1;
CREATE SEQUENCE payout_seq START WITH 1 INCREMENT BY 1;

-- =============================================
-- 2. Core Users & Roles
-- =============================================
CREATE TABLE users (
    user_id           NUMBER DEFAULT user_seq.NEXTVAL PRIMARY KEY,
    name              VARCHAR2(100) NOT NULL,
    email             VARCHAR2(100) UNIQUE NOT NULL,
    phone             VARCHAR2(20) NOT NULL,
    password_hash     VARCHAR2(255) NOT NULL,
    profile_image     VARCHAR2(255),
    gender            VARCHAR2(20),
    role              VARCHAR2(10) DEFAULT 'buyer' NOT NULL
        CONSTRAINT chk_user_role CHECK (role IN ('buyer','seller','admin')),
    status            VARCHAR2(20) DEFAULT 'pending' NOT NULL,
    verification_token VARCHAR2(64),
    token_expires_at  TIMESTAMP,
    created_at        TIMESTAMP DEFAULT SYSTIMESTAMP,
    updated_at        TIMESTAMP DEFAULT SYSTIMESTAMP
);

-- =============================================
-- 3. Kitchens & Service Zones
-- =============================================
CREATE TABLE kitchens (
    kitchen_id        NUMBER DEFAULT kitchen_seq.NEXTVAL PRIMARY KEY,
    owner_id          NUMBER NOT NULL
        CONSTRAINT fk_kitchen_owner REFERENCES users(user_id),
    name              VARCHAR2(100) NOT NULL,
    description       CLOB,
    cover_image       VARCHAR2(255),
    address           VARCHAR(255) NOT NULL,
    google_maps_url   VARCHAR2(255),
    years_experience  NUMBER(2),
    signature_dish    VARCHAR2(100),
    avg_prep_time     NUMBER(3) DEFAULT 30,
    approval_status   VARCHAR2(20) DEFAULT 'pending'
        CONSTRAINT chk_approval_status CHECK (LOWER(approval_status) IN ('pending','approved','rejected')),
    created_at        TIMESTAMP DEFAULT SYSTIMESTAMP,
    updated_at        TIMESTAMP DEFAULT SYSTIMESTAMP
);

CREATE TABLE service_areas (
    area_id           NUMBER DEFAULT area_seq.NEXTVAL PRIMARY KEY,
    name              VARCHAR2(100) NOT NULL,
    city              VARCHAR2(50) DEFAULT 'Dhaka' NOT NULL,
    status            VARCHAR2(10) DEFAULT 'active' CHECK (status IN ('active','inactive')),
    CONSTRAINT uq_service_area UNIQUE (name, city)
);

CREATE TABLE kitchen_service_zones (
    kitchen_id        NUMBER NOT NULL
        CONSTRAINT fk_ksz_kitchen REFERENCES kitchens(kitchen_id) ON DELETE CASCADE,
    area_id           NUMBER NOT NULL
        CONSTRAINT fk_ksz_area REFERENCES service_areas(area_id),
    delivery_fee      NUMBER(10,2) DEFAULT 30,
    min_order         NUMBER(10,2) DEFAULT 150,
    PRIMARY KEY (kitchen_id, area_id)
);

-- =============================================
-- 4. Categories & Menu
-- =============================================
CREATE TABLE categories (
    category_id       NUMBER DEFAULT category_seq.NEXTVAL PRIMARY KEY,
    name              VARCHAR2(50) NOT NULL,
    description       VARCHAR2(200),
    image             VARCHAR(255)
);

CREATE TABLE menu_items (
    item_id           NUMBER DEFAULT menu_item_seq.NEXTVAL PRIMARY KEY,
    kitchen_id        NUMBER NOT NULL
        CONSTRAINT fk_menu_kitchen REFERENCES kitchens(kitchen_id) ON DELETE CASCADE,
    name              VARCHAR2(100) NOT NULL,
    description       CLOB,
    portion_size      VARCHAR2(20),
    price             NUMBER(10,2) NOT NULL,
    spice_level       NUMBER(1) DEFAULT 1
        CONSTRAINT chk_spice_level CHECK (spice_level BETWEEN 1 AND 3),
    daily_stock       NUMBER DEFAULT 10,
    is_available      NUMBER(1) DEFAULT 1,
    item_image        VARCHAR2(255),
    created_at        TIMESTAMP DEFAULT SYSTIMESTAMP,
    updated_at        TIMESTAMP DEFAULT SYSTIMESTAMP
);

CREATE TABLE menu_item_categories (
    item_id           NUMBER NOT NULL
        CONSTRAINT fk_mic_item REFERENCES menu_items(item_id) ON DELETE CASCADE,
    category_id       NUMBER NOT NULL
        CONSTRAINT fk_mic_category REFERENCES categories(category_id),
    PRIMARY KEY (item_id, category_id)
);

-- =============================================
-- 5. Orders
-- =============================================
CREATE TABLE orders (
    order_id          NUMBER DEFAULT order_seq.NEXTVAL PRIMARY KEY,
    buyer_id          NUMBER NOT NULL
        CONSTRAINT fk_order_buyer REFERENCES users(user_id),
    kitchen_id        NUMBER NOT NULL
        CONSTRAINT fk_order_kitchen REFERENCES kitchens(kitchen_id),
    delivery_area_id  NUMBER
        CONSTRAINT fk_order_area REFERENCES service_areas(area_id),
    delivery_address  CLOB NOT NULL,
    contact_phone     VARCHAR2(20) NOT NULL,
    total_amount      NUMBER(10,2) NOT NULL,
    delivery_fee      NUMBER(10,2) DEFAULT 0,
    status            VARCHAR2(20) DEFAULT 'PENDING'
        CONSTRAINT chk_order_status CHECK (status IN ('PENDING', 'ACCEPTED', 'READY', 'DELIVERED', 'CANCELLED')),
    estimated_delivery_time NUMBER(3),
    actual_delivery_time TIMESTAMP,
    created_at        TIMESTAMP DEFAULT SYSTIMESTAMP,
    updated_at        TIMESTAMP DEFAULT SYSTIMESTAMP,
    buyer_delete      NUMBER(1) DEFAULT 0,
    cancellation_reason VARCHAR2(255),
    cancel_by VARCHAR2(10) 
);

CREATE TABLE order_items (
    order_item_id     NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    order_id          NUMBER NOT NULL
        CONSTRAINT fk_oi_order REFERENCES orders(order_id) ON DELETE CASCADE,
    item_id           NUMBER
        CONSTRAINT fk_oi_item REFERENCES menu_items(item_id),
    quantity          NUMBER NOT NULL,
    price_at_order    NUMBER(10,2) NOT NULL,
    special_request   VARCHAR2(100)
);

-- =============================================
-- 6. Subscriptions
-- =============================================
CREATE TABLE subscription_plans (
    plan_id           NUMBER DEFAULT plan_seq.NEXTVAL PRIMARY KEY,
    plan_name         VARCHAR2(50) NOT NULL,
    description       CLOB,
    monthly_fee       NUMBER(6) DEFAULT 0 NOT NULL,
    commission_rate   NUMBER(5,2) NOT NULL,
    max_items         NUMBER(2) DEFAULT 3,
    is_active         NUMBER(1) DEFAULT 0,
    is_highlight      NUMBER(1) DEFAULT 0,
    created_at        TIMESTAMP DEFAULT SYSTIMESTAMP,
    updated_at        TIMESTAMP DEFAULT SYSTIMESTAMP
);

CREATE TABLE seller_subscriptions (
    subscription_id   NUMBER DEFAULT subscription_seq.NEXTVAL PRIMARY KEY,
    seller_id         NUMBER NOT NULL
        CONSTRAINT fk_subscription_seller REFERENCES users(user_id),
    plan_id           NUMBER NOT NULL
        CONSTRAINT fk_subscription_plan REFERENCES subscription_plans(plan_id),
    start_date        DATE NOT NULL,
    end_date          DATE NOT NULL,
    status            VARCHAR2(20) DEFAULT 'ACTIVE' CHECK (status IN ('ACTIVE','PENDING','EXPIRED','CANCELLED')),
    change_type       VARCHAR2(20) DEFAULT 'NEW' CHECK (change_type IN ('NEW','UPGRADE','DOWNGRADE','RENEWAL')),
    created_at        TIMESTAMP DEFAULT SYSTIMESTAMP NOT NULL,
    updated_at        TIMESTAMP
);

-- =============================================
-- 7. Payments & Payouts & Refunds
-- =============================================
CREATE TABLE payment_transactions ( 
    id                NUMBER DEFAULT payment_transactions_seq.NEXTVAL PRIMARY KEY,
    transaction_id    VARCHAR2(50) UNIQUE NOT NULL,
    user_id           NUMBER NOT NULL
        CONSTRAINT pt_fk_user REFERENCES users(user_id),
    amount            NUMBER(10,2) NOT NULL, -- total ammount user paying for order or subscription
    currency          VARCHAR2(3) DEFAULT 'BDT',
    transaction_type  VARCHAR2(20) NOT NULL
        CONSTRAINT pt_chk_type CHECK (transaction_type IN ('PAYMENT','PAYOUT')),
    reference_type    VARCHAR2(20) NOT NULL
        CONSTRAINT pt_chk_ref_type CHECK (reference_type IN ('ORDER','SUBSCRIPTION','WITHDRAWAL','REFUND')),
    reference_id      NUMBER, -- SUBSCRIPTION ID / ORDER ID / WITHDRAWAL ID / REFUND ID
    payment_method    VARCHAR2(50), 
    status            VARCHAR2(20) NOT NULL
        CONSTRAINT pt_chk_status CHECK (status IN ('PENDING','SUCCESS','FAILED','CANCELLED')),
    description       VARCHAR2(500),
    gateway_response  CLOB,
    message           VARCHAR2(500),
    metadata          CLOB CONSTRAINT check_metadata_json CHECK (metadata IS NULL OR metadata IS JSON),
    created_at        TIMESTAMP DEFAULT SYSTIMESTAMP NOT NULL,
    updated_at        TIMESTAMP DEFAULT SYSTIMESTAMP NOT NULL
);

CREATE TABLE refund_requests (
    refund_id NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    order_id NUMBER NOT NULL,
    buyer_id NUMBER NOT NULL,
    amount NUMBER(10,2) NOT NULL,
    method VARCHAR2(50) NOT NULL, -- e.g., 'Bank Transfer', 'bKash', 'Nagad'
    account_details VARCHAR2(255) NOT NULL, -- target account info
    reason VARCHAR2(500),
    status VARCHAR2(20) DEFAULT 'PENDING' CHECK (status IN ('PENDING', 'APPROVED', 'REJECTED', 'PROCESSED')),
    created_at TIMESTAMP DEFAULT SYSTIMESTAMP,
    updated_at TIMESTAMP DEFAULT SYSTIMESTAMP,
    admin_notes VARCHAR2(500),
    buyer_delete NUMBER(1) DEFAULT 0,
    CONSTRAINT fk_refund_order FOREIGN KEY (order_id) REFERENCES orders(order_id),
    CONSTRAINT fk_refund_buyer FOREIGN KEY (buyer_id) REFERENCES users(user_id)
);

CREATE TABLE withdraw_requests (
    withdraw_id NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    seller_id NUMBER NOT NULL,
    amount NUMBER(10,2) NOT NULL,
    method VARCHAR2(50) NOT NULL, -- e.g., 'Bank Transfer', 'bKash', 'Nagad'
    account_details VARCHAR2(255) NOT NULL, -- target account info
    status VARCHAR2(20) DEFAULT 'PENDING' 
        CHECK (status IN ('PENDING', 'APPROVED', 'REJECTED', 'PROCESSED')),
    created_at TIMESTAMP DEFAULT SYSTIMESTAMP,
    updated_at TIMESTAMP DEFAULT SYSTIMESTAMP,
    admin_notes VARCHAR2(500),
    CONSTRAINT fk_withdraw_seller FOREIGN KEY (seller_id) REFERENCES users(user_id)
);

-- =============================================
-- 8. Buyer Engagement (Cart, Favorites, Reviews)
-- =============================================
CREATE TABLE cart (
    cart_id NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id NUMBER NOT NULL
        CONSTRAINT fk_cart_user REFERENCES users(user_id) ON DELETE CASCADE,
    item_id NUMBER NOT NULL
        CONSTRAINT fk_cart_item REFERENCES menu_items(item_id) ON DELETE CASCADE,
    quantity NUMBER DEFAULT 1 NOT NULL CHECK (quantity > 0),
    added_at TIMESTAMP DEFAULT SYSTIMESTAMP,
    CONSTRAINT uk_cart_user_item UNIQUE (user_id, item_id)
);

CREATE TABLE favorites (
    favorite_id   NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id       NUMBER NOT NULL
        CONSTRAINT fk_fav_user REFERENCES users(user_id) ON DELETE CASCADE,
    reference_type VARCHAR2(20) NOT NULL
        CONSTRAINT chk_fav_type CHECK (reference_type IN ('KITCHEN','ITEM')),
    reference_id   NUMBER NOT NULL, -- kitchen id or menu item id
    added_at       TIMESTAMP DEFAULT SYSTIMESTAMP,
    CONSTRAINT uq_fav UNIQUE (user_id, reference_type, reference_id)
);

-- Enhanced reviews table
CREATE TABLE reviews (
    review_id           NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    reviewer_id         NUMBER, 
    reference_id        NUMBER, -- kitchen id or menu item id or null for platform review tiffincraft
    reference_type      VARCHAR2(20) NOT NULL
        CONSTRAINT chk_rev_type CHECK (reference_type IN ('KITCHEN','ITEM','TIFFINCRAFT')),
    rating              NUMBER(1) CHECK (rating BETWEEN 1 AND 5),
    comments            CLOB,
    review_date         TIMESTAMP DEFAULT SYSTIMESTAMP,
    status              VARCHAR2(20) DEFAULT 'PUBLIC' 
        CHECK (status IN ('PUBLIC', 'HIDDEN', 'REPORTED')),
    hidden_by           NUMBER REFERENCES users(user_id),
    hidden_at           TIMESTAMP,
    hidden_reason       VARCHAR2(500),
    created_at          TIMESTAMP DEFAULT SYSTIMESTAMP,
    updated_at          TIMESTAMP DEFAULT SYSTIMESTAMP
);

-- =============================================
-- 9. Suspension
-- =============================================
CREATE TABLE suspensions (
    suspension_id       NUMBER GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    reference_id        NUMBER NOT NULL,
    reference_type      VARCHAR2(20) NOT NULL
        CONSTRAINT chk_sus_type CHECK (reference_type IN ('KITCHEN','USER')),
    reason              VARCHAR2(255) NOT NULL,
    suspended_at        TIMESTAMP DEFAULT SYSTIMESTAMP,
    suspended_until     TIMESTAMP NULL,
    created_by          NUMBER, -- admin id who suspended
    status              VARCHAR2(20) DEFAULT 'active' -- active / lifted
);


-- =============================================
-- 10. Indexes
-- =============================================
CREATE INDEX idx_kitchen_owner ON kitchens(owner_id);
CREATE INDEX idx_orders_buyer ON orders(buyer_id);
CREATE INDEX idx_reviews_kitchen ON reviews(kitchen_id);
CREATE INDEX idx_favorites_user ON favorites(user_id);F
CREATE INDEX idx_payment_reference ON payment_transactions(reference_type, reference_id);

-- =============================================
-- 11. Triggers
-- =============================================
CREATE OR REPLACE TRIGGER pt_update_trigger
BEFORE UPDATE ON payment_transactions
FOR EACH ROW
BEGIN
    :NEW.updated_at := SYSTIMESTAMP;
END;
/

DROP SEQUENCE tag_seq;
