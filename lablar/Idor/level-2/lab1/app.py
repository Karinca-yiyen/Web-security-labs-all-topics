from flask import Flask, render_template, request, jsonify, session, redirect, url_for, abort
import sqlite3, secrets

app = Flask(__name__)
app.secret_key = "level1-local-lab-secret"
DB = "shop.db"

def db():
    conn = sqlite3.connect(DB)
    conn.row_factory = sqlite3.Row
    return conn

def init_db():
    conn = db()
    conn.executescript("""
    DROP TABLE IF EXISTS orders;
    DROP TABLE IF EXISTS customers;
    DROP TABLE IF EXISTS security_profiles;
    DROP TABLE IF EXISTS accounts;

    CREATE TABLE accounts (
        id INTEGER PRIMARY KEY,
        username TEXT UNIQUE,
        password TEXT,
        role TEXT
    );

    CREATE TABLE customers (
        id INTEGER PRIMARY KEY,
        account_id INTEGER,
        full_name TEXT,
        email TEXT,
        phone TEXT,
        address TEXT,
        security_profile_id INTEGER
    );

    CREATE TABLE orders (
        id INTEGER PRIMARY KEY,
        customer_id INTEGER,
        product TEXT,
        total REAL,
        status TEXT
    );

    CREATE TABLE security_profiles (
        id INTEGER PRIMARY KEY,
        account_id INTEGER,
        recovery_email TEXT,
        recovery_token TEXT
    );
    """)

    accounts = [
        (1, "osman", "demo123", "customer"),
        (2, "yagiz", "demo123", "customer"),
        (99, "admin", "Adm1n-Lab-Onlysdasdsdaasd", "admin"),
    ]
    conn.executemany("INSERT INTO accounts VALUES (?,?,?,?)", accounts)

    customers = [
        (101, 1, "yagız yılmaz", "azad@bomba.com", "+90 555 111 1111",
         "bitlis / Türkiye", 501),
        (102, 2, "osman abuhan", "azad@bomba", "+90 555 222 2222",
         "bitlis / Türkiye", 502),
        (999, 99, "NovaMart Admin", "admin@novamart.local", "+90 555 999 9999",
         "NovaMart HQ", 599),
    ]
    conn.executemany("INSERT INTO customers VALUES (?,?,?,?,?,?,?)", customers)

    orders = [
        (7001, 101, "NovaBook Pro 14", 38999, "Shipped"),
        (7002, 101, "NovaMouse X", 1299, "Delivered"),
        (7003, 102, "NovaPhone Ultra", 42999, "Processing"),
        (7999, 999, "Admin Console License", 0, "Internal"),
    ]
    conn.executemany("INSERT INTO orders VALUES (?,?,?,?,?)", orders)

    profiles = [
        (501, 1, "backup-ayse@novamart.local", secrets.token_hex(16)),
        (502, 2, "backup-mehmet@novamart.local", secrets.token_hex(16)),
        (599, 99, "recovery-admin@novamart.local", "ADMIN-RECOVERY-LAB-9F42"),
    ]
    conn.executemany("INSERT INTO security_profiles VALUES (?,?,?,?)", profiles)

    conn.commit()
    conn.close()

@app.route("/")
def index():
    return render_template("index.html")

@app.route("/login", methods=["GET", "POST"])
def login():
    if request.method == "POST":
        username = request.form["username"]
        password = request.form["password"]
        conn = db()
        user = conn.execute(
            "SELECT * FROM accounts WHERE username=? AND password=?",
            (username, password)
        ).fetchone()
        conn.close()
        if user:
            session["account_id"] = user["id"]
            session["username"] = user["username"]
            session["role"] = user["role"]
            return redirect(url_for("dashboard"))
        return render_template("login.html", error="Invalid credentials / Geçersiz bilgiler")
    return render_template("login.html")

@app.route("/dashboard")
def dashboard():
    if "account_id" not in session:
        return redirect(url_for("login"))
    conn = db()
    orders = conn.execute(
        "SELECT * FROM orders WHERE customer_id=(SELECT id FROM customers WHERE account_id=?)",
        (session["account_id"],)
    ).fetchall()
    conn.close()
    return render_template("dashboard.html", orders=orders)

# IDOR #1:
# The endpoint accepts an order_id but never verifies that the logged-in
# account owns that order. The response deliberately exposes customer_id.
@app.route("/api/orders/<int:order_id>")
def api_order(order_id):
    if "account_id" not in session:
        return jsonify(error="login required"), 401
    conn = db()
    row = conn.execute("""
        SELECT id, customer_id, product, total, status
        FROM orders WHERE id=?
    """, (order_id,)).fetchone()
    conn.close()
    if not row:
        return jsonify(error="order not found"), 404
    return jsonify(dict(row))

# IDOR #2:
# customer_id is treated as a direct object reference without ownership check.
@app.route("/api/customers/<int:customer_id>")
def api_customer(customer_id):
    if "account_id" not in session:
        return jsonify(error="login required"), 401
    conn = db()
    row = conn.execute("""
        SELECT id, account_id, full_name, email, phone, address, security_profile_id
        FROM customers WHERE id=?
    """, (customer_id,)).fetchone()
    conn.close()
    if not row:
        return jsonify(error="customer not found"), 404
    return jsonify(dict(row))

# IDOR #3:
# security_profile_id is another direct reference. It returns the recovery
# material belonging to the referenced account. In this lab the final object
# contains a local-only recovery token for the admin account.
@app.route("/api/security-profiles/<int:profile_id>")
def api_security_profile(profile_id):
    if "account_id" not in session:
        return jsonify(error="login required"), 401
    conn = db()
    row = conn.execute("""
        SELECT id, account_id, recovery_email, recovery_token
        FROM security_profiles WHERE id=?
    """, (profile_id,)).fetchone()
    conn.close()
    if not row:
        return jsonify(error="profile not found"), 404
    return jsonify(dict(row))

@app.route("/admin-recovery/<token>")
def admin_recovery(token):
    conn = db()
    row = conn.execute(
        "SELECT * FROM security_profiles WHERE recovery_token=? AND account_id=99",
        (token,)
    ).fetchone()
    conn.close()
    if not row:
        return "Invalid recovery token / Geçersiz recovery token", 403
    session["account_id"] = 99
    session["username"] = "admin"
    session["role"] = "admin"
    return redirect(url_for("admin"))

@app.route("/admin")
def admin():
    if session.get("account_id") != 99:
        return render_template("denied.html"), 403
    return render_template("admin.html")

@app.route("/logout")
def logout():
    session.clear()
    return redirect(url_for("index"))

if __name__ == "__main__":
    init_db()
    app.run(host="127.0.0.1", port=5000, debug=False)
