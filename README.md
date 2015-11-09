# Google Mirror #
**for Chinese version,click [here](README_zh.md).**
A PHP program to set up a mirror server of Google search service in minutes.

---

#### Guide for Setting up ####

**Deploy:**
- Before setting up, you need :
	- PHP 5.4+ runtime environment (outside China mainland and etc)
	- a wildcard certificate for the domain you  want to set mirror up on
	- SSL configured for the domain to be used (only available on 443 port)
- Download all the file included in the repo (except README.md and LISENCE)
- Open the public.php, and replace the default domain to yours ($pHost = "yourdomain.com";)
- Upload files to your host
- It works! 

**Notice:**
- About DNS record: (@.)yourdomain.com & *.yourdomain.com point to your host
