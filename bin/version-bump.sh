#!/bin/bash

# Version bump script for VAPT Security plugin

# Check if version type is provided
if [ $# -eq 0 ]; then
    echo "Usage: $0 <major|minor|patch>"
    echo "Example: $0 patch"
    exit 1
fi

VERSION_TYPE=$1

# Get current version from plugin file
CURRENT_VERSION=$(grep -oP '\* Version:\s*\K[0-9]+\.[0-9]+\.[0-9]+' vapt-security.php)

# Parse version components
MAJOR=$(echo $CURRENT_VERSION | cut -d. -f1)
MINOR=$(echo $CURRENT_VERSION | cut -d. -f2)
PATCH=$(echo $CURRENT_VERSION | cut -d. -f3)

# Increment version based on type
case $VERSION_TYPE in
    "major")
        MAJOR=$((MAJOR + 1))
        MINOR=0
        PATCH=0
        ;;
    "minor")
        MINOR=$((MINOR + 1))
        PATCH=0
        ;;
    "patch")
        PATCH=$((PATCH + 1))
        ;;
    *)
        echo "Invalid version type. Use major, minor, or patch."
        exit 1
        ;;
esac

NEW_VERSION="$MAJOR.$MINOR.$PATCH"

echo "Bumping version from $CURRENT_VERSION to $NEW_VERSION"

# Update version in plugin file
sed -i "s/\* Version:\s*[0-9]\+\.[0-9]\+\.[0-9]\+/* Version:     $NEW_VERSION/" vapt-security.php

# Update version in README.txt if it exists
if [ -f "README.txt" ]; then
    sed -i "s/Stable tag:\s*[0-9]\+\.[0-9]\+\.[0-9]\+/Stable tag: $NEW_VERSION/" README.txt
fi

# Add changes to git
git add vapt-security.php CHANGELOG.md

# Commit with version bump message
git commit -m "Bump version to $NEW_VERSION"

# Create tag
git tag -a "v$NEW_VERSION" -m "Version $NEW_VERSION"

echo "Version bumped to $NEW_VERSION"
echo "Changes committed and tagged"