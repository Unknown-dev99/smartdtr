# XAMPP Apache setup for SMARTDTR subdirectory

If you see **404 Not Found** at `http://localhost/INHS SMART-DTR/`, enable Apache rewrite support:

## 1. Enable mod_rewrite

Edit `C:\xampp\apache\conf\httpd.conf` and uncomment:

```apache
LoadModule rewrite_module modules/mod_rewrite.so
```

## 2. Allow .htaccess overrides

In the same file, find the `<Directory "C:/xampp/htdocs">` block and set:

```apache
AllowOverride All
```

## 3. Restart Apache

Use the XAMPP Control Panel: **Stop** Apache, then **Start**.

## 4. Open the app

```
http://localhost/INHS%20SMART-DTR/
```

Login: `admin` / `Admin@123`

---

## Optional: cleaner URL with a virtual host

Add to `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/INHS SMART-DTR/public"
    ServerName smartdtr.local
    <Directory "C:/xampp/htdocs/INHS SMART-DTR/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Add to `C:\Windows\System32\drivers\etc\hosts`:

```
127.0.0.1 smartdtr.local
```

Then open: `http://smartdtr.local/login`

Set in `.env`:

```env
APP_URL=http://smartdtr.local
```
