# Security Policy

## Supported Versions

We are committed to providing security updates for the following versions of Klytron Deployer:

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |
| < 1.0   | :x:                |

## Reporting a Vulnerability

We take security vulnerabilities seriously. If you discover a security vulnerability in Klytron Deployer, please follow these steps:

### 1. **DO NOT** Create a Public Issue

- **Do not** report security vulnerabilities through public GitHub issues
- **Do not** discuss security vulnerabilities in public forums or discussions
- **Do not** post security vulnerabilities on social media

### 2. Report Privately

Please report security vulnerabilities privately to:

- **Email**: hi@klytron.com
- **Subject**: `[SECURITY] Klytron Deployer Vulnerability Report`

### 3. Include Required Information

Your security report should include:

- **Description**: Clear description of the vulnerability
- **Impact**: Potential impact of the vulnerability
- **Steps to Reproduce**: Detailed steps to reproduce the issue
- **Environment**: Your environment details (OS, PHP version, etc.)
- **Proof of Concept**: If possible, include a proof of concept
- **Suggested Fix**: If you have suggestions for fixing the issue

### 4. Response Timeline

We commit to:

- **Initial Response**: Within 48 hours of receiving your report
- **Status Update**: Regular updates on the progress of fixing the issue
- **Public Disclosure**: Coordinated disclosure once the fix is available

### 5. Responsible Disclosure

We follow responsible disclosure practices:

- We will work with you to understand and validate the vulnerability
- We will develop and test a fix
- We will release a security update
- We will publicly acknowledge your contribution (if you wish)

## Security Best Practices

### For Users

1. **Keep Updated**: Always use the latest stable version
2. **Environment Files**: Never commit `.env` files to version control
3. **SSH Keys**: Use secure SSH key authentication
4. **Permissions**: Set appropriate file and directory permissions
5. **HTTPS**: Always use HTTPS in production
6. **Backups**: Regularly backup your applications and databases
7. **Monitoring**: Monitor your deployments for suspicious activity

### For Contributors

1. **Code Review**: All code changes are reviewed for security issues
2. **Testing**: Security-related changes are thoroughly tested
3. **Documentation**: Security features are properly documented
4. **Dependencies**: Keep dependencies updated and secure

## Security Features

Klytron Deployer includes several security features:

### Authentication & Authorization

- SSH key authentication
- Environment validation
- Production safety checks
- Permission validation

### Data Protection

- Secure environment file handling
- Database credential protection
- Backup encryption support
- Secure logging

### Deployment Security

- Deployment locking
- Rollback capabilities
- Health checks
- Error handling

## Known Security Considerations

### SSH Key Management

- Store SSH keys securely with appropriate permissions (600)
- Use passphrase-protected SSH keys
- Rotate SSH keys regularly
- Use SSH agents for key management

### Environment Files

- Never commit `.env` files to version control
- Use environment-specific configuration files
- Validate environment variables
- Use strong, unique passwords

### File Permissions

- Set appropriate file permissions (644 for files, 755 for directories)
- Use proper ownership for web server user
- Regularly audit file permissions
- Use `chown` instead of `chmod` when possible

### Database Security

- Use strong database passwords
- Limit database user permissions
- Use SSL/TLS for database connections
- Regularly backup databases

## Security Updates

### How We Handle Security Updates

1. **Assessment**: We assess the severity and impact of the vulnerability
2. **Fix Development**: We develop a fix and test it thoroughly
3. **Release**: We release a security update with appropriate versioning
4. **Documentation**: We document the vulnerability and fix
5. **Notification**: We notify users through appropriate channels

### Versioning for Security Updates

- **Patch Release**: For security fixes (e.g., 1.0.1)
- **Minor Release**: For security features (e.g., 1.1.0)
- **Major Release**: For breaking security changes (e.g., 2.0.0)

## Security Contact

For security-related questions or concerns:

- **Email**: hi@klytron.com
- **Response Time**: Within 48 hours
- **Encryption**: PGP key available upon request

## Security Acknowledgments

We would like to thank the security researchers and community members who have responsibly reported vulnerabilities and helped improve the security of Klytron Deployer.

## Security Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)
- [Deployer Security](https://deployer.org/docs/security)
- [SSH Security Best Practices](https://www.ssh.com/academy/ssh/security)

## Security Policy Updates

This security policy may be updated from time to time. Significant changes will be announced through:

- GitHub releases
- Project documentation
- Email notifications (for critical updates)

---

**Last Updated**: January 2024

**Version**: 1.0
