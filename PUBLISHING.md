# Publishing to Packagist

## Overview

This guide documents the process for publishing `klytron/php-deployment-kit` to [Packagist](https://packagist.org).

---

## ✅ Pre-Release Checklist

- [x] No private paths, credentials, or secrets in any file
- [x] No references to `secure-conf-repo` or private infrastructure tools
- [x] `SECURITY_AUDIT_REPORT.md` excluded from Packagist archives via `.gitattributes`
- [x] `GITHUB_REPO_DETAILS.md` excluded from Packagist archives via `.gitattributes`
- [x] `composer.json` version matches `CHANGELOG.md` (currently `1.0.0`)
- [x] `CHANGELOG.md` has a dated version entry (no `[Unreleased]`)
- [x] GitHub repo is public: `https://github.com/klytron/php-deployment-kit`
- [x] `MIT` license in place
- [x] `SECURITY.md` with responsible disclosure info
- [ ] Create a Git tag for the release (see below)
- [ ] Submit to Packagist (see below)
- [ ] Set up Packagist webhook for auto-updates (see below)

---

## 1. Create a Release Tag

```bash
cd /path/to/php-deployment-kit

git tag -a v1.0.0 -m "v1.0.0: Initial public release"
git push origin v1.0.0
```

---

## 2. Submit to Packagist

1. Log in at [https://packagist.org](https://packagist.org)
2. Go to: [https://packagist.org/packages/submit](https://packagist.org/packages/submit)
3. Paste the GitHub URL: `https://github.com/klytron/php-deployment-kit`
4. Click **Check**, then **Submit**

The package will be available as:
```bash
composer require klytron/php-deployment-kit --dev
```

---

## 3. Set Up Packagist Auto-Update Webhook

So Packagist updates automatically when you push:

1. On Packagist, go to the package page → **Click your username** → **Profile** → copy the **API token**
2. In GitHub: **Repo Settings** → **Webhooks** → **Add webhook**
   - **Payload URL**: `https://packagist.org/api/github?username=YOUR_PACKAGIST_USERNAME`
   - **Content type**: `application/json`
   - **Secret**: your Packagist API token
   - **Trigger**: `Just the push event`
3. Click **Add webhook**

---

## 4. Release Workflow (Future Releases)

```bash
# 1. Update CHANGELOG.md (add new version block)
# 2. Bump version in composer.json
# 3. Commit
git add -A
git commit -m "chore: release v1.x.x"
git push

# 4. Tag the release
git tag -a v1.x.x -m "Release notes..."
git push origin v1.x.x
# Packagist auto-updates via webhook
```

---

## 5. Verify the Release

After submitting and tagging, verify:

```bash
# Install in a test project
composer require klytron/php-deployment-kit:1.2.0 --dev

# Check available version
composer show klytron/php-deployment-kit
```

---

*Last updated: February 2026*
