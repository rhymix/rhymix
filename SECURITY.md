Security Policy
---------------

### Supported Versions

Only the latest version is actively supported.

### Reporting a Vulnerability

Please report vulnerabilities by email to devops@rhymix.org.
Please DO NOT use GitHub issues, pull requests, or any other public channel for this purpose.

You may also use GitHub Security Advisories to reach us.
This will be associated with your GitHub account once a fix has been published,
and it may facilitate CVE assignment.

If you are a Korean security researcher, you can report vulnerabilities through KISA's KNVD.
KISA will review the information and pass it along to us without identifying you.

### Note on Superuser Accounts

We do not consider it a vulnerability if the superuser (is_admin=Y) account
can insert scripts or delete information. That's what the superuser account is for!

It will, however, be considered a serious vulnerability if someone else can
trick a superuser to perform such actions inadvertently,
for example through a CSRF attack.
