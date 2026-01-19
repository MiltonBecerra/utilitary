Hostinger deployment (shared hosting, PHP 8.1, no Node)

Target subdomain:
- utilitary.kunakui.com

Expected layout on server:
- Project root: public_html/utilitary
- Document root for the subdomain: public_html/utilitary/public

Steps
1) Upload the full project to public_html/utilitary (including vendor/).
2) In Hostinger -> Subdomains -> Document Root, set:
   public_html/utilitary/public
3) Create .env in public_html/utilitary using deploy/env.production.example as base.
4) Ensure permissions:
   chmod -R 775 storage bootstrap/cache
5) From SSH, run:
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   php artisan migrate --force

Notes
- No Node is required.
- If you cannot change the document root, you must move the contents of public/ to
  public_html/utilitary and adjust public_html/utilitary/index.php paths accordingly.
  The recommended approach is setting the document root to public/.
