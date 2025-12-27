# Version Control System

This document explains the version control system implemented for the VAPT Security plugin.

## Versioning Scheme

We follow Semantic Versioning (SemVer) with the format: `MAJOR.MINOR.PATCH`

- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

## Current Version

Current version: **4.1.2**

## Version Bumping

### Automated Scripts

Two scripts are provided to automate version bumps:

1. **Windows**: `bin/version-bump.bat`
2. **Linux/Mac**: `bin/version-bump.sh`

### Usage

```bash
# For Windows
bin\version-bump.bat patch

# For Linux/Mac
./bin/version-bump.sh minor

# Version types:
# - major: Increments MAJOR version (resets MINOR and PATCH to 0)
# - minor: Increments MINOR version (resets PATCH to 0)
# - patch: Increments PATCH version
```

### What the Scripts Do

1. Read current version from `vapt-security.php`
2. Increment version based on specified type
3. Update version in:
   - Main plugin file header
   - README.txt (Stable tag)
4. Add changes to Git
5. Commit with standardized message
6. Create Git tag

## Manual Version Update

If you prefer to update versions manually:

1. Edit `vapt-security.php` and update the version in the header:
   ```php
   * Version:     X.Y.Z
   ```

2. Edit `README.txt` and update the Stable tag:
   ```
   Stable tag: X.Y.Z
   ```

3. Update `CHANGELOG.md` with release notes

4. Commit changes:
   ```bash
   git add vapt-security.php README.txt CHANGELOG.md
   git commit -m "Bump version to X.Y.Z"
   ```

5. Create tag:
   ```bash
   git tag -a "vX.Y.Z" -m "Version X.Y.Z"
   ```

## Release Process

1. Bump version using automated script or manual process
2. Update CHANGELOG.md with release notes
3. Push changes and tags:
   ```bash
   git push origin main
   git push origin --tags
   ```
4. Package plugin for distribution if needed

## Branching Strategy

- **main**: Production-ready code
- **develop**: Development branch for upcoming features
- **feature/***: Feature branches
- **hotfix/***: Hotfix branches for urgent fixes

## Best Practices

1. Always bump version before releasing
2. Document all changes in CHANGELOG.md
3. Create tags for all releases
4. Follow SemVer principles
5. Keep version numbers in sync across all files