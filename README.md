# JSON Message Processor (PHP)

This command-line application processes a list of service messages from a JSON file, classifies them into `inspection` or `failure_report` entities based on custom business rules, filters out duplicate descriptions, and outputs the results as JSON files.

It also logs important processing steps to the console and prints a nice metrics summary at the end.

## Requirements

- **PHP**: `^8.0` (Tested on `8.3.31`)
- **Composer**: `^2.0`

## Installation

Clone the repository and install the dependencies:

```bash
composer install
```

## Running the Application

You can invoke the application from the command line by passing the path to the input JSON file:

```bash
php bin/console app:process /path/to/source.json --out-dir=./out
```

### Options

- `--out-dir` or `-o`: Specifies the output directory where JSON files will be generated (default: `./out`).

### Example

```bash
php bin/console app:process recruitment-task-source.json --out-dir=./output
```

### Output Files

The tool produces three JSON files in the specified output directory:

1. `inspections.json` - contains only inspection entities.
2. `failure_reports.json` - contains only failure report entities.
3. `failed_messages.json` - contains original messages that could not be processed (e.g., duplicate descriptions, missing required fields) along with a short explanation of the reason.

## Running Tests

Automated tests are written with PHPUnit and verify the parsing, classification, priority evaluation, phone mapping, and duplicate detection.

To run the test suite:

```bash
vendor/bin/phpunit
```

## Architecture and Extensibility

The application is built using clean, decoupled components ready for future extensions:

- **DTO / Models**: Uses distinct models (`Inspection`, `FailureReport`, `FailedMessage`) implementing `JsonSerializable` for precise serialization control.
- **`MessageValidator`**: Performs initial structural validation on incoming raw data.
- **`ClassifierInterface` & `KeywordClassifier`**: Encapsulates the classification rules. Supports **both Polish and English** keywords (e.g., `przegląd` and `inspection`) to ensure robust cross-lingual parsing.
- **`DateParser`**: Gracefully parses standard dates, times, empty strings, nulls, and malformed date strings.
- **`MessageProcessor`**: Coordinates validation, duplicate detection, mapping, and output generation.
- **`ProcessCommand`**: A clean Symfony Console command handling user inputs, generating directories, writing output files, and displaying execution metrics.
