#!/bin/bash
# Script to run PHPUnit tests

# Check if composer dependencies are installed
if [ ! -d "vendor" ]; then
    echo "Installing dependencies..."
    composer install --dev
fi

# Run all tests by default
if [ $# -eq 0 ]; then
    echo "Running all tests..."
    ./vendor/bin/phpunit
else
    # Run specific test file if provided
    echo "Running test: $1"
    ./vendor/bin/phpunit "$1"
fi