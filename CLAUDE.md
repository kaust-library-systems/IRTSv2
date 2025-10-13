# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

IRTS (Institutional Research Tracking System) is a PHP-based web application that tracks research output for KAUST Library. It harvests metadata from multiple academic sources and provides interfaces for reviewing and managing research publications to ensure compliance with Open Access policies.

## Commands

This is a PHP project using Composer for dependency management:

- **Install dependencies**: `composer install`
- **Run harvest tasks**: `/data/scripts/launch_script.sh /var/www/irts/tasks/harvest.php source=<source_name>`
- **Run update tasks**: `/data/scripts/launch_script.sh /var/www/irts/bin/runTask.php task=update process=<process_name>`

Key harvest sources: `arxiv`, `crossref`, `europePMC`, `github`, `ieee`, `scopus`, `unpaywall`, `wos`, `dspace`, `repository`

## Architecture

### Core Components

1. **Entry Point**: `include.php` - Auto-loads all configuration and function files from predefined directories
2. **Configuration**:
   - `config/constants_template.php` - API URLs and system constants
   - `config/credentials_template.php` - API keys and credentials
   - `config/shared/` - Shared database connections and configurations
3. **Functions**: Modular PHP functions split between IRTS-specific (`functions/`) and shared (`functions/shared/`)
4. **Sources**: Individual modules for each metadata source (`sources/*/`) with source-specific harvesting logic
5. **Web Interface**: `public_html/forms/reviewCenter.php` - Primary user interface for metadata review

### Database Schema

- `metadata` - Harvested metadata from all sources in standardized format
- `sourceData` - Original XML/JSON from metadata sources
- `mappings` - Field mappings from source formats to Dublin Core
- `transformations` - Rules for transforming metadata values
- `messages` - Logs from automated tasks
- `users` - User access control for forms

### Automated Tasks

**Harvest Tasks** (`tasks/harvest.php`):
- External sources: Daily harvest of multiple academic databases
- DSpace REST API: Every 10 minutes for repository updates
- OAI-PMH: Daily harvest for repository structure and files

**Update Tasks** (`bin/runTask.php`):
- Embargo expiration notifications
- ORCID updates from institutional systems
- Dashboard data preparation for Power BI

### Development Notes

- All PHP files are included automatically via `include.php` - avoid duplicate includes
- Template files (`*_template.php`) are excluded from auto-loading
- The system expects to run from `/var/www/irts/` path in production
- Database connections and most configuration loaded from shared config directory
- Each metadata source has its own directory with specialized harvesting functions