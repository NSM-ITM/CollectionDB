# National Science Museum - Information Technology Museum - Collection Database, Dublin Core Metadata
## Powererd by [Omeka](http://omeka.org)

---

## Minimum System Requirement (2016-present)
- Omeka	2.4.1
- PHP 5.6.30
- MySQL Server 5.5.5
- Apache 2.4.25
- TO-DO Consider switching to nginx with load balancer and CDN to serve in high traffic environment

---

## Installation
- Edit db.ini to connect Omeka with MySQL database
- Enable mod_rewrite extension
- Execute installation steps as usual to initiate database structure and setup administrator account
- Once finished installation, visit plugins page in settings menu to check if all required plugins are installed

---

## Extra Development
- ItemLoans Plugin (NSM in-house development for Omeka 2.4.1)

---

Note: current implementation, including database and collection files, are operating within NSM intranet