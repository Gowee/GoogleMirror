# Google Mirror #
**for Chinese version,click [here](readme_zh.md).**

---
A PHP program to set up a mirror server of Google search service in minutes.

---

#### Introduction of Each Folders  ####

- SearchMirror :
	- core scripts to proxy user's request and send them to Google search server
- ResFetch :
	- proxy some static web scripts and images

---
#### Guide for Setting up ####
---
- Before setting up, you need :
	- PHP 5.4+ runtime environment (outside China mainland and etc)
	- two web host associated with (sub or top) domains (one for search, one for static resources)
	- SSL configured for the domain to be used (only available on 443 port)
- download and unzip the whole project folder
- replace the demo domains with your own domains in each PHP scripts (e.g. www.ppx.pw => www.example.com; static.ppx.pw => res.example.com)
- deploy all the scripts in "SearchMirror" to one host and scripts in "ResFetch" to another host
- and then you will see your mirror server running

---