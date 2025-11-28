# Deep Analysis of `exportMetadataAndPublicFilesInBatches.php`

## Purpose
This function exports research publication metadata and associated PDF files from a DSpace institutional repository to a directory for SDAIA (Saudi Data & AI Authority). It specifically targets KAUST-affiliated research papers and ETDs (Electronic Theses and Dissertations).

---

## Workflow Breakdown

### 1. Configuration & Setup (Lines 16-30)
- Maps Dublin Core metadata fields to human-readable labels
- Creates CSV headers including Handle, File, Type, Title, Author, DOI, Publication Date, Repository Record Created
- Initializes output array and performance timer

### 2. Record Selection Query (Lines 32-61)

**Primary Criteria:**
- Source: DSpace repository
- Document Types: Articles, Books, Conference Papers, Dissertations, Theses, etc. (Line 35)
- Communities: Two specific KAUST collections (`10754/324602`, `10754/124545`) - Lines 39-40
- Only non-deleted records

**Optional Date Filtering (Lines 46-61):**
- If `?from=YYYY-MM-DD` parameter exists, only fetches records with new bitstreams added after that date
- Uses a complex subquery to identify records with bitstream UUIDs NOT present before the specified date

### 3. Embargo Filtering (Lines 66-74)
```php
// Excludes records with active embargoes
$embargoedHandles = getValues(..., "dc.rights.embargodate >= TODAY");
$handles = array_diff($handles, $embargoedHandles);
```

### 4. Duplicate Prevention (Lines 78-86)
- Scans the export directory for existing files
- Extracts handle suffixes from filenames to avoid re-downloading

### 5. Main Processing Loop (Lines 90-178)

**For each handle:**

a. **Metadata Extraction (Lines 106-117)**
   - Fetches Type, Title, Author, DOI, dates
   - Handles multiple types by taking first value only (Line 114)

b. **File UUID Retrieval (Lines 119-139)**
   - Complex query targeting PDFs in the ORIGINAL bundle
   - Filters for `.pdf` files in bitstream metadata

c. **File Download Logic (Lines 142-174)**
   - **If file exists**: Adds metadata row, skips download
   - **If file missing**:
     - Calls `dspaceGetBitstreamsContent($fileUUID)`
     - Writes binary content to `SDAIA_EXPORT_DIRECTORY/[handleSuffix].pdf`
     - Breaks after first successful PDF download per handle (Line 170)

d. **Performance Management (Lines 176-177)**
   - Resets execution time limit
   - Flushes output buffer for long-running processes

### 6. CSV Export (Lines 180-197)
Writes all metadata rows to `metadata.csv` in the export directory

---

## Key Design Decisions

| Aspect | Implementation | Implication |
|--------|----------------|-------------|
| **One PDF per record** | `break;` on line 170 | If multiple PDFs exist, only first is exported |
| **Filename convention** | `{handleSuffix}.pdf` | e.g., handle `10754/123456` → `123456.pdf` |
| **Incremental exports** | `existingHandleSuffixes` check | Supports resumable batch processing |
| **Date filtering** | Bitstream-based (not item creation) | Tracks when files were added, not metadata |

---

## Potential Issues & Improvements

### Security Concerns

1. **SQL Injection Risk (Lines 56, 58):**
   ```php
   AND `added` < '".$_GET['from']."'  // ⚠️ Unsanitized user input
   ```
   Should use parameterized queries or sanitization

2. **Path Traversal (Line 159):**
   No validation that `$fileName` is safe before writing to filesystem

### Reliability Issues

1. **Silent Failures**: If no PDFs found for a handle, no row is added to CSV (Line 167 only executes on success)
2. **Ambiguous Success Count**: `$recordTypeCounts['success']` counts file downloads, not total exported records
3. **No Error Handling**: `dspaceGetBitstreamsContent()` failures are silently skipped

### Performance

- No batch size limiting - processes ALL matching handles in one execution
- Memory could grow unbounded with large result sets

---

## Dependencies

### External Functions
- `getValues()` - Database query wrapper
- `setSourceMetadataQuery()` - Query builder for metadata table
- `dspaceGetBitstreamsContent()` - DSpace REST API client
- `saveReport()` - Logging/reporting function

### Constants
- `SDAIA_EXPORT_DIRECTORY` - Target filesystem path
- `TODAY` - Current date constant

---

## Recommended Improvements

### 1. Sanitize Date Parameter
```php
$fromDate = isset($_GET['from']) ? mysqli_real_escape_string($irts, $_GET['from']) : null;
// Or better yet, use prepared statements
```

### 2. Track All Exported Records
```php
// Track all exported records, not just successful downloads
if($recordAdded) {
    $recordTypeCounts['success']++;
}
```

### 3. Add Error Logging
```php
if($response['status'] !== 'success') {
    $errors[] = "Failed to download $handle: " . ($response['error'] ?? 'Unknown error');
    echo " - ERROR: Could not retrieve file.\n";
}
```

### 4. Validate Filename Safety
```php
// Sanitize filename to prevent path traversal
$handleSuffix = basename(explode('/', $handle)[1]);
$fileName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $handleSuffix) . '.pdf';
```

### 5. Add Batch Size Control
```php
// Process in configurable batches
$batchSize = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$handles = array_slice($handles, 0, $batchSize);
```

---

## Usage Examples

### Basic Export
```bash
/data/scripts/launch_script.sh /var/www/irts/bin/runTask.php task=update process=exportMetadataAndPublicFilesInBatches
```

### Incremental Export (from specific date)
```bash
# Export only records with bitstreams added after January 1, 2024
/data/scripts/launch_script.sh /var/www/irts/bin/runTask.php task=update process=exportMetadataAndPublicFilesInBatches from=2024-01-01
```

---

## Output

### Files Created
- `{SDAIA_EXPORT_DIRECTORY}/metadata.csv` - CSV file with all metadata
- `{SDAIA_EXPORT_DIRECTORY}/{handleSuffix}.pdf` - PDF files for each record

### CSV Structure
```
Handle,File,Type,Title,Author,DOI,Publication Date,Repository Record Created
http://hdl.handle.net/10754/123456,123456.pdf,Article,Sample Title,Smith, John,10.1000/xyz,2024-01-01,2024-01-15
```
