# Google Mirror #
**for Chinese version,click [here](readme_zh.md).**

---
A PHP program to set up a mirror server of Google search service in minutes.

---

#### Guide for Setting up ####

---

**Deploy:**
- Before setting up, you need :
	- PHP 5.4+ runtime environment (outside China mainland and etc)
	- a wildcard certificate for the domain you  want to set mirror up on
	- SSL configured for the domain to be used (only available on 443 port)
- Download all the file included in the repo (except README.md and LISENCE)
- Open the public.php, and replace the default domain to yours ($pHost = "yourdomain.com";)
- It works! 

**Notice:**
- About DNS record: www. needs pointing at least, and ipv4.&&ipv6. which can handle the captcha service for the server under a lot of requests is recommended.
