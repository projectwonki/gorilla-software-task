# JSON Message Classifier

A simple PHP CLI application that parses service messages from a JSON file, validates and deduplicates them, and classifies them into `inspections` and `failure_reports`.

## Requirements

- PHP 8.0+
- Composer

## Getting Started

1. Install dependencies:
   ```bash
   composer install
   ```

2. Run the processor command:
   ```bash
   php bin/console app:process recruitment-task-source.json --out-dir=./output
   ```

### Command Options

- `--out-dir` / `-o`: Directory where output JSON files will be created (defaults to `./out`).

## Output Files

The tool generates three JSON files in the output directory:
- `inspections.json`: Messages classified as inspections.
- `failure_reports.json`: Messages classified as failure reports.
- `failed_messages.json`: Messages that failed validation or were flagged as duplicates, including the failure reason.

## Running Tests

Run the test suite using PHPUnit:

```bash
vendor/bin/phpunit
```

## Implementation Notes

- **Validation & Deduplication**: The command validates input structure (requiring `number` and a non-empty `description`) and filters out messages with duplicate descriptions (case-insensitive).
- **Classification**: Messages containing "przegląd" or "inspection" in the description are categorized as inspections; others are treated as failure reports.
- **Date Handling**: Due dates are parsed into formatted dates (`Y-m-d`). Inspections with valid dates are set to `scheduled` status (with computed ISO weeks), while failure reports with valid dates are marked as `appointment`. Missing or invalid dates default to `new`.
- **Phone Numbers**: Normalizes phone fields, leaving them empty if they contain invalid characters or no digits.
