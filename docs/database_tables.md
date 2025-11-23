Database Tables
===============

# IRTS

## deletedMetadata
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| rowID | int | NO | PRI | None | auto_increment |
| source | varchar(50) | NO | MUL | None |  |
| idInSource | varchar(150) | NO | MUL | None |  |
| parentRowID | int | YES | MUL | None |  |
| field | varchar(200) | NO | MUL | None |  |
| place | int | NO | MUL | 1 |  |
| value | longtext | NO | MUL | None |  |
| added | timestamp | NO | MUL | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
| deleted | timestamp | YES | MUL | None |  |
| replacedByRowID | int | YES |  | None |  |
## deletedSourceData
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| rowID | int | NO | PRI | None | auto_increment |
| source | varchar(30) | NO | MUL | None |  |
| idInSource | varchar(150) | NO | MUL | None |  |
| sourceData | longtext | NO |  | None |  |
| format | varchar(10) | NO |  | None |  |
| added | timestamp | NO |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
| deleted | timestamp | YES | MUL | None |  |
| replacedByRowID | int | YES | MUL | None |  |
## mappings
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| mappingID | int | NO | PRI | None | auto_increment |
| source | varchar(30) | NO |  | None |  |
| parentFieldInSource | varchar(30) | NO |  | None |  |
| sourceField | varchar(100) | NO |  | None |  |
| standardField | varchar(50) | NO |  | None |  |
## messages
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| messageID | int | NO | PRI | None | auto_increment |
| process | varchar(200) | NO | MUL | None |  |
| type | varchar(20) | NO |  | None |  |
| message | longtext | NO |  | None |  |
| timestamp | timestamp | NO |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
## metadata
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| rowID | int | NO | PRI | None | auto_increment |
| source | varchar(50) | NO | MUL | None |  |
| idInSource | varchar(150) | NO | MUL | None |  |
| parentRowID | int | YES | MUL | None |  |
| field | varchar(200) | NO | MUL | None |  |
| place | int | NO | MUL | 1 |  |
| value | longtext | NO | MUL | None |  |
| added | timestamp | NO | MUL | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
| deleted | timestamp | YES | MUL | None |  |
| replacedByRowID | int | YES |  | None |  |
## sourceData
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| rowID | int | NO | PRI | None | auto_increment |
| source | varchar(30) | NO | MUL | None |  |
| idInSource | varchar(150) | NO | MUL | None |  |
| sourceData | longtext | NO |  | None |  |
| format | varchar(10) | NO |  | None |  |
| added | timestamp | NO |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
| deleted | timestamp | YES | MUL | None |  |
| replacedByRowID | int | YES | MUL | None |  |
## transformations
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| transformationID | int | NO | PRI | None | auto_increment |
| source | varchar(50) | NO | MUL | None |  |
| field | varchar(100) | NO |  | None |  |
| place | int | NO |  | 1 |  |
| type | varchar(50) | NO | MUL | None |  |
| transformation | varchar(200) | NO |  | None |  |
## users
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| rowID | int | NO | PRI | None | auto_increment |
| email | varchar(100) | NO |  | None |  |
| role | varchar(100) | NO |  | None |  |
| added | timestamp | NO |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |

# Repository

## authors
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| Item Handle | varchar(14) | NO |  | None |  |
| Author Name | text | NO |  | None |  |
| Place | varchar(10) | NO |  | None |  |
| KAUST Affiliated | varchar(3) | NO |  | None |  |
| ORCID | varchar(30) | NO |  | None |  |
| Pushed To ORCID | varchar(3) | NO |  | None |  |
| Row Modified | datetime | NO |  | None |  |
## collections
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| Collection Handle | varchar(14) | NO | PRI | None |  |
| Collection Name | text | NO |  | None |  |
| Row Modified | datetime | NO | MUL | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
## communities
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| Community Handle | varchar(14) | NO | PRI | None |  |
| Community Name | text | NO |  | None |  |
| Row Modified | datetime | NO | MUL | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
## departments
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| Department ID | varchar(50) | NO |  | None |  |
| Department Type | varchar(50) | NO |  | None |  |
| Collection Handle | varchar(14) | NO |  | None |  |
| Collection Name | text | NO |  | None |  |
| Community Handle | varchar(14) | NO |  | None |  |
| Department Name | text | NO |  | None |  |
| Department Abbreviation | varchar(50) | NO |  | None |  |
| Row Modified | datetime | NO |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
## dois
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| Item Handle | varchar(20) | NO |  | None |  |
| DOI | varchar(50) | NO |  | None |  |
| Status | varchar(20) | NO |  | None |  |
| Row Modified | datetime | NO |  | None |  |
## downloads
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| Handle | varchar(12) | YES |  | None |  |
| Page URL | text | NO |  | None |  |
| Visitor Type | varchar(50) | YES |  | None |  |
| Referrer String | mediumtext | YES |  | None |  |
| Referrer Name | mediumtext | YES |  | None |  |
| Country | varchar(50) | YES |  | None |  |
| Year | int | YES |  | None |  |
| Month | int | YES |  | None |  |
| Year and Month as DateTime | datetime | YES |  | None |  |
| Downloads | int | YES |  | None |  |
| Row Modified | datetime | NO |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
## epersons
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| id | varchar(50) | NO |  | None |  |
| name | varchar(50) | YES |  | None |  |
| firstname | varchar(50) | YES |  | None |  |
| lastname | varchar(50) | YES |  | None |  |
| netid | varchar(50) | YES |  | None |  |
| lastActive | varchar(100) | YES |  | None |  |
| canLogIn | varchar(10) | YES |  | None |  |
| email | varchar(50) | YES |  | None |  |
| requireCertificate | varchar(10) | YES |  | None |  |
| selfRegistered | varchar(10) | YES |  | None |  |
## epersonsToGroups
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| eperson name | varchar(50) | NO |  | None |  |
| eperson id | varchar(50) | NO |  | None |  |
| group name | text | NO |  | None |  |
| group id | varchar(50) | NO |  | None |  |
## files
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| Item Handle | varchar(14) | NO |  | None |  |
| Bundle Name | varchar(50) | NO |  | None |  |
| File Order | int | NO |  | None |  |
| File URL | text | NO |  | None |  |
| File Name | text | NO |  | None |  |
| File Size in Bytes | bigint | NO |  | None |  |
| File Type | varchar(250) | NO |  | None |  |
| File Description | text | NO |  | None |  |
| Row Modified | datetime | NO |  | None |  |
## `groups`
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| id | varchar(50) | NO |  | None |  |
| name | text | NO |  | None |  |
| permanent | varchar(10) | NO |  | None |  |
## handles
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| Handle | varchar(14) | NO | PRI | None |  |
| Handle Type | varchar(50) | NO |  | None |  |
| Row Modified | datetime | NO | MUL | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
## itemFilters
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| Item Handle | varchar(14) | NO | PRI | None |  |
| Publication Year | year | YES |  | None |  |
| File Deposited | varchar(3) | YES |  | None |  |
| File Under Embargo | varchar(3) | YES |  | None |  |
| Open Access Color | varchar(7) | YES |  | None |  |
| Type of File Deposited | varchar(51) | YES |  | None |  |
| Has KAUST DOI | varchar(3) | YES |  | None |  |
| Has KAUST Faculty Author | varchar(3) | YES |  | None |  |
| Has KAUST Affiliated Author | varchar(3) | YES |  | None |  |
| All Authors are KAUST Affiliated | varchar(3) | YES |  | None |  |
| KAUST Mention in Acknowledgement | varchar(3) | YES |  | None |  |
| KAUST Grant Number Acknowledgement | varchar(3) | YES |  | None |  |
| KAUST Department or Lab Acknowledgement | varchar(3) | YES |  | None |  |
| Row Modified | datetime | NO |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
## items
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| Item Handle | varchar(14) | NO | PRI | None |  |
| DOI | varchar(250) | YES |  | None |  |
| Item Type | varchar(50) | YES |  | None |  |
| Title | text | YES |  | None |  |
| Publisher | varchar(250) | YES |  | None |  |
| Venue | text | YES |  | None |  |
| Record Visibility | varchar(10) | YES |  | None |  |
| Publication Date | varchar(23) | YES |  | None |  |
| Embargo End Date | varchar(11) | YES |  | None |  |
| Row Modified | datetime | NO | MUL | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
## itemsToCollections
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| Item Handle | varchar(14) | NO |  | None |  |
| Collection Handle | varchar(14) | NO |  | None |  |
| Row Modified | datetime | NO |  | None |  |
## itemsToCommunities
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| Item Handle | varchar(14) | NO |  | None |  |
| Community Handle | varchar(14) | NO |  | None |  |
| Row Modified | datetime | NO |  | None |  |
## itemsToDepartments
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| Item Handle | varchar(14) | NO |  | None |  |
| Department Name | text | NO |  | None |  |
| Row Modified | datetime | NO |  | None |  |
## metadataReviewStatus
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| ID in IRTS | varchar(50) | YES |  | None |  |
| Type in IRTS | varchar(50) | YES |  | None |  |
| Source | varchar(50) | YES |  | None |  |
| ID in Source | varchar(250) | YES |  | None |  |
| DOI | varchar(250) | YES |  | None |  |
| Has DOI | varchar(3) | YES |  | None |  |
| Item Handle | varchar(14) | YES |  | None |  |
| Has Repository Record | varchar(3) | YES |  | None |  |
| Status | varchar(50) | YES |  | None |  |
| Note | mediumtext | YES |  | None |  |
| Harvest Basis | varchar(250) | YES |  | None |  |
| Date Harvested | datetime | YES |  | None |  |
| Date Processed | datetime | YES |  | None |  |
| Processed By | varchar(50) | YES |  | None |  |
| Days in Queue | int | YES |  | None |  |
| Process Time in Metadata Review Form (Minutes) | int | YES |  | None |  |
| Row Modified | datetime | YES |  | None |  |
## metadataSourceRecords
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| Source | varchar(50) | YES |  | None |  |
| ID in Source | varchar(200) | YES |  | None |  |
| Type in Source | varchar(50) | YES |  | None |  |
| Title in Source | text | YES |  | None |  |
| Publication Date in Source | varchar(10) | YES |  | None |  |
| Publication Year in Source | varchar(4) | YES |  | None |  |
| Citation Count | int | YES |  | None |  |
| Has KAUST Affiliation in Source | varchar(3) | NO |  | None |  |
| Has KAUST Acknowledgement in Source | varchar(3) | NO |  | None |  |
| DOI | varchar(200) | YES |  | None |  |
| Has DOI | varchar(3) | YES |  | None |  |
| Handle | varchar(14) | YES |  | None |  |
| Has Repository Record | varchar(3) | YES |  | None |  |
| ID in IRTS | varchar(200) | YES |  | None |  |
| Has IRTS Process Entry | varchar(3) | YES |  | None |  |
| First Source | varchar(10) | YES |  | None |  |
| Only Source | varchar(10) | YES |  | None |  |
| Date of First Harvest | varchar(10) | YES |  | None |  |
| Date of Last Update | varchar(10) | YES |  | None |  |
| Row Modified | datetime | NO |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
## orcids
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| ORCID | varchar(30) | NO |  | None |  |
| Permissions Granted | varchar(3) | NO |  | None |  |
| Permissions Granted Date | datetime | YES |  | None |  |
| Permissions Scope | varchar(200) | NO |  | None |  |
| Permissions Status | varchar(20) | NO |  | None |  |
| Employment Entries Pushed to ORCID | int | NO |  | None |  |
| Education Entries Pushed to ORCID | int | NO |  | None |  |
| Work Entries Pushed to ORCID | int | NO |  | None |  |
| Row Modified | datetime | NO |  | None |  |
## pageViews
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| Handle | varchar(12) | YES |  | None |  |
| Page URL | mediumtext | YES |  | None |  |
| Page Type | varchar(250) | YES |  | None |  |
| Referrer String | mediumtext | YES |  | None |  |
| Referrer Name | varchar(50) | YES |  | None |  |
| Known Referrer | varchar(3) | YES |  | None |  |
| Country | varchar(50) | YES |  | None |  |
| Year | int | YES |  | None |  |
| Month | int | YES |  | None |  |
| Year and Month as DateTime | datetime | YES |  | None |  |
| Page Views | int | YES |  | None |  |
| Row Modified | datetime | NO |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
## publisherAgreements
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| Publisher ID | int | YES |  | None |  |
| Publisher Name | text | YES |  | None |  |
| Agreement Type | varchar(23) | YES |  | None |  |
| Eligible Authors | varchar(26) | YES |  | None |  |
| Start Date | varchar(10) | YES |  | None |  |
| End Date | varchar(10) | YES |  | None |  |
| Active | varchar(3) | YES |  | None |  |
## publishers
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| Publisher ID | int | NO | PRI | None |  |
| Publisher Name | text | YES |  | None |  |
| Has Active Agreement | varchar(3) | YES |  | None |  |
| Row Modified | datetime | NO |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |

# doiMinter

## dois
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| rowID | int | NO | PRI | None | auto_increment |
| handle | varchar(50) | NO | MUL | None |  |
| doi | varchar(50) | NO | UNI | None |  |
| url | varchar(100) | NO |  | None |  |
| type | varchar(20) | NO |  | None |  |
| status | varchar(20) | NO |  | None |  |
| added | timestamp | NO |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
## messages
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| messageID | int | NO | PRI | None | auto_increment |
| process | varchar(50) | NO |  | None |  |
| type | varchar(20) | NO |  | None |  |
| message | longtext | NO |  | None |  |
| timestamp | timestamp | NO |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
## sourceData
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| rowID | int | NO | PRI | None | auto_increment |
| source | varchar(30) | NO | MUL | None |  |
| idInSource | varchar(100) | NO | MUL | None |  |
| sourceData | longtext | NO |  | None |  |
| format | varchar(10) | NO |  | None |  |
| added | timestamp | NO | MUL | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
| deleted | timestamp | YES | MUL | None |  |
| replacedByRowID | int | YES | MUL | None |  |

# IOI

## Non_KAUST_Affiliated_Publications_for_FAAR
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| rowID | int | NO | PRI | None | auto_increment |
| KAUST_ID | int | YES | MUL | None |  |
| Name | mediumtext | YES |  | None |  |
| FAAR_Year | int | YES | MUL | None |  |
| Type | mediumtext | YES |  | None |  |
| Title | mediumtext | YES |  | None |  |
| Authors | mediumtext | YES |  | None |  |
| Journal | mediumtext | YES |  | None |  |
| DOI | mediumtext | YES |  | None |  |
| Handle | mediumtext | NO |  | None |  |
| Publication Date | varchar(10) | YES |  | None |  |
| Citation | mediumtext | YES |  | None |  |
| Status | mediumtext | YES |  | None |  |
## accessKeys
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| keyID | int | NO | PRI | None | auto_increment |
| accessKey | varchar(100) | NO |  | None |  |
| allowedEndpoint | varchar(100) | NO |  | None |  |
| applicationName | varchar(100) | NO |  | None |  |
| contactEmail | varchar(100) | NO |  | None |  |
| created | timestamp | NO |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
| deleted | timestamp | YES |  | None |  |
## emailTemplates
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| templateID | int | NO | PRI | None | auto_increment |
| label | varchar(50) | NO |  | None |  |
| template | longtext | NO |  | None |  |
| lastUpdated | timestamp | NO |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
## `groups`
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| groupID | int | NO | PRI | None | auto_increment |
| label | varchar(50) | NO | UNI | None |  |
| titles | mediumtext | NO |  | None |  |
| titleParts | mediumtext | NO |  | None |  |
| titlePartsToIgnore | mediumtext | NO |  | None |  |
## mappings
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| mappingID | int | NO | PRI | None | auto_increment |
| source | varchar(50) | NO |  | None |  |
| sourceField | varchar(50) | NO |  | None |  |
| entryType | varchar(50) | NO |  | None |  |
| place | int | YES |  | None |  |
| orcidField | varchar(50) | NO |  | None |  |
## messages
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| messageID | int | NO | PRI | None | auto_increment |
| process | varchar(50) | NO | MUL | None |  |
| type | varchar(20) | NO | MUL | None |  |
| message | longtext | NO |  | None |  |
| timestamp | timestamp | NO | MUL | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
## orcids
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| rowID | int | NO | PRI | None | auto_increment |
| email | varchar(100) | NO | MUL | None |  |
| orcid | varchar(50) | NO | UNI | None |  |
| name | varchar(100) | NO |  | None |  |
| localPersonID | int | NO |  | None |  |
| added | timestamp | NO |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
| deleted | timestamp | YES |  | None |  |
## putCodes
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| rowID | int | NO | PRI | None | auto_increment |
| orcid | varchar(30) | NO | MUL | None |  |
| type | varchar(30) | NO | MUL | None |  |
| putCode | varchar(30) | NO | MUL | None |  |
| localSourceRecordID | varchar(100) | NO | MUL | None |  |
| submittedData | longtext | NO |  | None |  |
| format | varchar(10) | NO | MUL | None |  |
| apiResponse | mediumtext | NO |  | None |  |
| added | timestamp | NO | MUL | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
| deleted | timestamp | YES | MUL | None |  |
| replacedByRowID | int | YES | MUL | None |  |
## queryLog
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| queryID | int | NO | PRI | None | auto_increment |
| accessKey | varchar(100) | YES | MUL | None |  |
| requestType | varchar(10) | YES | MUL | None |  |
| responseFormat | varchar(10) | YES | MUL | None |  |
| timeElapsed | varchar(15) | NO |  | None |  |
| report | longtext | NO |  | None |  |
| timestamp | timestamp | NO | MUL | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
## tokens
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| access_token | varchar(50) | NO | PRI | None |  |
| expiration | datetime | NO |  | None |  |
| scope | varchar(200) | NO |  | None |  |
| orcid | varchar(50) | NO | MUL | None |  |
| name | varchar(100) | NO |  | None |  |
| created | datetime | NO |  | None |  |
| refresh_token | varchar(200) | YES |  | None |  |
| deleted | timestamp | YES | MUL | None |  |
## userSelections
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| rowID | int | NO | PRI | None | auto_increment |
| orcid | varchar(30) | NO |  | None |  |
| type | varchar(30) | NO |  | None |  |
| localSourceRecordID | varchar(255) | NO |  | None |  |
| selected | timestamp | YES |  | None |  |
| ignored | timestamp | YES |  | None |  |
| deleted | timestamp | YES |  | None |  |
## users
| Field | Type | Null | Key | Default | Extra |
| ----- | ---- | ---- | --- | ------- | ----- |
| email | varchar(100) | NO | PRI | None |  |
| admin | int | NO |  | None |  |
| added | timestamp | NO |  | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
