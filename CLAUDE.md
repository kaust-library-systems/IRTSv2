# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

IRTS (Institutional Research Tracking System) is a PHP-based web application that tracks research output for KAUST Library. It harvests metadata from multiple academic sources and provides interfaces for reviewing and managing research publications to ensure compliance with Open Access policies.

## Commands

This is a PHP project using Composer for dependency management:

- **Install dependencies**: `composer install`
- **Run harvest tasks**: `/data/scripts/launch_script.sh /var/www/irts/tasks/harvest.php source=<source_name>`
- **Run update tasks**: `/data/scripts/launch_script.sh /var/www/irts/bin/runTask.php task=update process=<process_name>`
- **Sync from production**: `./sync-from-production.sh [--dry-run]` - Synchronizes code from `/home/garcm0b/Work/irts/` (production) to this directory

### Available Metadata Sources

IRTS harvests from 25+ metadata sources organized in `sources/*/`:

**Academic Databases**: `arxiv`, `crossref`, `datacite`, `doi`, `europePMC`, `ieee`, `lens`, `ncbi`, `scopus`, `scienceDirect`, `semanticScholar`, `unpaywall`, `wos`

**Publisher/Patent Sources**: `googlePatents`, `googleScholar`, `sherpa`

**Institutional Systems**: `dspace`, `github`, `local`, `orcid`, `pure`, `repository`

**Special Collections**: `ebird` (ornithological research data)

## Architecture

### Core Components

1. **Entry Point**: `include.php` - Auto-loads all configuration and function files from predefined directories
2. **Configuration**:
   - `config/constants_template.php` - API URLs and system constants
   - `config/credentials_template.php` - API keys and credentials
   - `config/shared/` - Shared database connections and configurations
   - Template files are excluded from git; actual config files (`*_template.php` → `.php`) are git-ignored
3. **Functions**: Modular PHP functions organized into:
   - `functions/` - IRTS-specific functions
   - `functions/shared/` - Shared utility functions
   - `functions/forDashboards/` - Power BI/analytics dashboard preparation (26 files)
   - `functions/forPureXML/` - Pure system integration and XML generation (27 files)
   - `functions/publisherAgreements/` - Publisher agreement management (4 files)
   - `functions/forRepositoryExports/` - Repository data export functionality (2 files)
   - `functions/shared/dspace/` - DSpace-specific shared functions
   - `functions/shared/powerAutomate/` - Power Automate integration functions
4. **Sources**: Individual modules for each metadata source (`sources/*/`) with source-specific harvesting logic
5. **Web Interface**:
   - `public_html/forms/reviewCenter.php` - Primary user interface for metadata review
   - `public_html/publisherAgreement/PA.php` - Publisher agreement interface
   - `snippets/` - Reusable HTML/action components for forms (organized by workflow)
6. **Update Tasks**: `updates/` directory contains 60+ task implementation files for various automated processes

### Database Connections

IRTS connects to **5 databases** defined in `config/shared/database_template.php`:

1. **IRTS_DATABASE** - Main application database (metadata, sourceData, etc.)
2. **IOI_DATABASE** - Institutional Object Identifier system
3. **DOIMINTER_DATABASE** - DOI Minting service
4. **GOOGLE_ANALYTICS_DATABASE** - Analytics data
5. **REPOSITORY_DATABASE** - DSpace repository database

### Database Schema

**IRTS Database Core Tables**:
- `metadata` - Harvested metadata from all sources in standardized format
- `sourceData` - Original XML/JSON from metadata sources
- `deletedMetadata` - Tracks deleted metadata records
- `deletedSourceData` - Tracks deleted source data
- `mappings` - Field mappings from source formats to Dublin Core
- `transformations` - Rules for transforming metadata values
- `messages` - Logs from automated tasks
- `users` - User access control for forms

**Repository Database Tables** (accessed via REPOSITORY_DATABASE connection):
- `authors`, `collections`, `communities`, `items`, and other DSpace-related tables

See `docs/database_tables.md` for complete schema documentation.

### External System Integrations

IRTS integrates with multiple external systems:

- **Pure** - Research information management system (XML export/import)
- **DSpace** - Digital repository (REST API and database access)
- **ORCID** - Researcher identifier system
- **Power Automate** - Microsoft workflow automation
- **DOI Minter** - DOI registration service
- **Google Analytics** - Usage tracking and reporting

### Form Snippet System

The `snippets/` directory contains reusable form components organized by workflow:

- `forManuscriptRequest/` - Manuscript submission workflow
- `forMetadataEntry/` - Metadata entry forms
- `forDirectSubmissionApproval/` - Direct submission approval process
- `forManuscriptReceipt/` - Manuscript receipt handling
- `forEmbargoExtension/` - Embargo extension requests
- `forThesisApproval/` - ETD (thesis/dissertation) approval workflow
- `reviewCenterActions/` - Actions available in review center
- `html/` - Shared HTML components

### Automated Tasks

**Harvest Tasks** (`tasks/harvest.php`):
- External sources: Daily harvest of multiple academic databases
- DSpace REST API: Every 10 minutes for repository updates
- OAI-PMH: Daily harvest for repository structure and files

**Update Tasks** (`bin/runTask.php`):
The `updates/` directory contains 60+ specialized task files including:
- Embargo expiration notifications
- ORCID updates from institutional systems
- Dashboard data preparation for Power BI
- ETD (thesis/dissertation) management
- Author and affiliation handling
- Repository item display management
- Duplicate detection and merging
- Email notifications (various types)
- Resource policy management
- Collection and community mapping
- And many more specialized tasks...

### Development Environment

- **DevContainer**: `.devcontainer/devcontainer.json` provides VS Code development container (PHP 8.2)
- **Composer**: `composer.json` defines minimal dependencies (bibtex-parser)
- **Production Sync**: `sync-from-production.sh` synchronizes code from production IRTS installation
- **Documentation**: `docs/` contains database schemas and export documentation

## PHP Development Guidelines

### PHP Version & Code Style

**Production runs PHP 8.2.29, but codebase uses PHP 5.x-style procedural code:**
- ❌ No strict types, type hints, or return type declarations
- ❌ No modern PHP 8 features (enums, readonly properties, match expressions, attributes)
- ✅ Pure procedural functions (no classes or OOP patterns)
- ✅ Global function namespace via auto-loader

**IMPORTANT:** Maintain consistency with existing procedural style. Do NOT introduce modern PHP features in isolation.

### Function Definition Pattern

One function per file, filename matches function name:

```php
<?php
    // Define function to [clear description]
    function functionName($param1, $param2, $param3)
    {
        global $irts, $errors; // Declare globals at top

        // Implementation

        return $result;
    }
```

### Database Interaction Patterns

**Always use prepared statement wrappers** from `functions/shared/preparedStatements.php`:

```php
// SELECT queries
$result = select($irts, "SELECT * FROM metadata WHERE source LIKE ? AND field LIKE ?",
    array($source, $field));

// INSERT
insert($irts, 'metadata', array('source', 'idInSource', 'field', 'value'),
    array($source, $id, $field, $value));

// UPDATE
update($irts, 'metadata', array('deleted', 'replacedByRowID'),
    array(date("Y-m-d H:i:s"), $newRowID),
    array($existingRowID), 'rowID');

// DELETE
delete($irts, 'metadata', 'rowID', $rowID);
```

**NEVER build SQL strings with variables** - always use prepared statement functions to prevent SQL injection.

### Global Variables

Common globals used throughout the codebase:
- `$irts` - Main IRTS database connection (mysqli object)
- `$ioi`, `$repository`, `$doiMinter`, `$googleAnalytics` - Other database connections
- `$errors` - Array of error messages collected during execution
- `$newInProcess` - Count of new items flagged for processing
- `$recordTypeCounts` - Array tracking harvest statistics by type
- `$report` - Text report of harvest/update operations

Always declare globals at the top of functions that need them: `global $irts, $errors;`

### Error Handling Pattern

**No exceptions or try-catch blocks** - use global error array:

```php
global $errors;

if ($failure) {
    $errors[] = array('type'=>'database', 'message'=>"Error description: " . $detail);
    return FALSE;
}

// At end of harvest/update function
$report = saveReport($irts, 'processName', $report, $recordTypeCounts, $errors, $startTime);
```

### Creating New Metadata Sources

1. Create directory: `sources/{sourceName}/`
2. Add directory to `$directoriesToInclude` array in `include.php`
3. Create required files:
   - `harvest{SourceName}.php` - Main entry point function
   - `process{SourceName}Record.php` - Transform source data to metadata
   - `query{SourceName}.php` (optional) - API interaction functions

4. Follow the three-step harvest pattern:
   ```php
   // Step 1: Query/retrieve source data
   $records = query{SourceName}($parameters);

   // Step 2: Save raw source data
   saveSourceData($irts, $source, $idInSource, $data, $format);

   // Step 3: Process and map to metadata
   $result = process{SourceName}Record($record);
   mapTransformSave($source, $idInSource, $element, $field, $value, ...);
   ```

### API Integration Pattern

**Always use the `makeCurlRequest()` wrapper** from `functions/shared/makeCurlRequest.php`:

```php
$response = makeCurlRequest([
    CURLOPT_URL => $apiUrl,
    CURLOPT_HTTPHEADER => array("Authorization: Bearer " . $token)
], $expectedResponseCode);

if ($response['status'] === 'success') {
    $data = json_decode($response['body'], TRUE);
    // Process data
} else {
    $errors[] = array('type'=>'api', 'message'=>$response['error']);
}
```

### Metadata Field Naming Convention

All metadata fields use namespace.element.qualifier format:
- **Dublin Core standard**: `dc.identifier.doi`, `dc.title`, `dc.contributor.author`, `dc.date.issued`
- **Source-specific**: `crossref.type`, `scopus.citationCount`, `wos.accessionNumber`
- **Relations**: `dc.relation.ispartof`, `dc.relation.dataset`, `dc.relation.hasmanifestation`

### Naming Conventions

- **Files**: `camelCase.php` (e.g., `mapField.php`, `saveValue.php`)
- **Functions**: `camelCase()` (e.g., `mapField()`, `saveValue()`)
- **Source functions**: `{action}{SourceName}()` in PascalCase (e.g., `harvestCrossref()`, `processScopusRecord()`)
- **Variables**: `camelCase` (e.g., `$idInSource`, `$parentRowID`, `$recordTypeCounts`)
- **Constants**: `SCREAMING_SNAKE_CASE` (e.g., `CROSSREF_API`, `OAPOLICY_START_DATE`)
- **Database fields**: Dublin Core `namespace.element.qualifier` for metadata; `camelCase` for table columns

### Configuration & Template Files

Template files (`*_template.php`) define configuration structure:
- **NOT auto-loaded** by `include.php` (excluded by `strpos($file, '_template')` check)
- Located in `config/` and `config/shared/`
- **Deployment process**:
  1. Copy `credentials_template.php` → `credentials.php`
  2. Fill in production values (API keys, database passwords, etc.)
  3. Production files are git-ignored to protect credentials

### Security Best Practices

**CRITICAL - Always follow these security rules:**

✅ **DO:**
- Use prepared statement wrappers (`select`, `insert`, `update`, `delete`) for ALL database operations
- Validate and sanitize ALL `$_GET`, `$_POST`, `$_SESSION` input before use
- Escape output with `htmlspecialchars()` when displaying user data in HTML
- Validate API credentials exist before making requests
- Use `makeCurlRequest()` wrapper for all HTTP requests

❌ **NEVER:**
- Concatenate variables directly into SQL queries
- Trust user input without validation
- Display raw database values in HTML without escaping
- Hard-code credentials in source files (use template pattern)
- Use unsanitized filenames in file operations

### Common Pitfalls to Avoid

**DO NOT:**
- ❌ Use `require` or `include` for project files (everything auto-loads via `include.php`)
- ❌ Create multi-function files (one function per file pattern)
- ❌ Add modern PHP 8 features without full system refactor
- ❌ Use OOP/class patterns (maintain procedural consistency)
- ❌ Skip adding new source directories to `include.php` auto-loader
- ❌ Build SQL with string concatenation or interpolation

**DO:**
- ✅ Follow one-function-per-file pattern
- ✅ Declare global variables at top of functions
- ✅ Log all errors to global `$errors` array
- ✅ Use metadata versioning (set `deleted` and `replacedByRowID` instead of UPDATE)
- ✅ Save harvest reports via `saveReport()` function
- ✅ Maintain existing procedural coding style

### Development Notes

- All PHP files are included automatically via `include.php` - avoid duplicate includes
- Template files (`*_template.php`) are excluded from auto-loading
- The system expects to run from `/var/www/irts/` path in production
- Database connections and configuration loaded from shared config directory (supports multi-database architecture)
- Each metadata source has its own directory with specialized harvesting functions
- Configuration files (without `_template` suffix) are git-ignored and must be created from templates
- The snippet system allows modular form construction with reusable workflow components