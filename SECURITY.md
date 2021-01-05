Security Policy
---------------

### Supported Versions

Only the latest version is actively supported.

## Reporting a Vulnerability

Please report possible vulnerabilities by email to devops@rhymix.org.
Please DO NOT use GitHub issues or pull requests for this purpose.

We do not consider it a vulnerability if the superuser (is_admin=Y) account
can insert scripts or delete information. That's what the superuser account is for!
It will, however, be considered a serious vulnerability if someone else can
trick a superuser to perform such actions inadvertently,
for example through a CSRF attack.
