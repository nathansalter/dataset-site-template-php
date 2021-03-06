# dataset-site-template-php
PHP Classes and resources supporting dataset site creation, to create something like [this example](https://opendata.fusion-lifestyle.com/OpenActive/) (or any of the other examples listed [here](http://status.openactive.io/)).

This package intends to simplify creation of [OpenActive Dataset Sites](https://developer.openactive.io/publishing-data/dataset-sites) using templates.

For comparison, see the [.NET](https://github.com/openactive/OpenActive.DatasetSite.NET/) and [Ruby](https://github.com/openactive/dataset-site-template-ruby) implementations.

Please find full documentation that covers creation of the accompanying GitHub issues board at https://developer.openactive.io/publishing-data/dataset-sites.


## Table Of Contents
- [Requirements](#requirements)
- [Usage](#usage)
    - [API](#api)
        - [`renderSimpleDatasetSite($settings, $supportedFeedTypes)`](#rendersimpledatasetsitesettings-supportedfeedtypes)
        - [`renderSimpleDatasetSiteFromDataDownloads($settings, $dataDownloads, $dataFeedDescriptions)`](#rendersimpledatasetsitefromdatadownloadssettings-datadownloads-datafeeddescriptions)
        - [`renderDatasetSite($dataset)`](#renderdatasetsitedataset)
        - [`FeedType`](#feedtype)
- [Contribution](#contribution)
    - [Installation](#installation)
    - [Example](#example)
    - [Running Tests](#running-tests)

## Requirements
This project requires PHP >=5.6.
While most of the functionality should work down to PHP 5.4, some functionality (especially around parsing of offset for DateTimeZone) will not work with that version of PHP (see the [DateTimeZone PHP docs](https://www.php.net/manual/en/datetimezone.construct.php#refsect1-datetimezone.construct-changelog) for more info).

[Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos) is also required for dependency management.

This project also makes use of [Mustache](https://github.com/bobthecow/mustache.php) for rendering the template (installed via Composer).

## Usage

**Please Note:** This instruction are temporary and based on the current development status.

If you are developing this package, go to the [Contribution](#contribution) section.

To install from terminal, run:
```bash
composer require openactive/dataset-site
```

Wherever you want to render your Dataset page, include the following instructions:
```php
use OpenActive\DatasetSiteTemplate\TemplateRenderer;

// Render compiled template with data
echo (new TemplateRenderer())->renderSimpleDatasetSite($settings, $supportedFeedTypes);
```

Where `$settings` could be defined like the following (as an example):
```php
$settings = array(
    "openDataFeedBaseUrl" => "https://customer.example.com/feed/",
    "datasetSiteUrl" => "https://halo-odi.legendonlineservices.co.uk/openactive/",
    "datasetDiscussionUrl" => "https://github.com/gll-better/opendata",
    "datasetDocumentationUrl" => "https://docs.acmebooker.example.com/",
    "datasetLanguages" => array("en-GB"),
    "organisationName" => "Better",
    "organisationUrl" => "https://www.better.org.uk/",
    "organisationLegalEntity" => "GLL",
    "organisationPlainTextDescription" => "Established in 1993, GLL is the largest UK-based charitable social enterprise delivering leisure, health and community services. Under the consumer facing brand Better, we operate 258 public Sports and Leisure facilities, 88 libraries, 10 children’s centres and 5 adventure playgrounds in partnership with 50 local councils, public agencies and sporting organisations. Better leisure facilities enjoy 46 million visitors a year and have more than 650,000 members.",
    "organisationLogoUrl" => "http://data.better.org.uk/images/logo.png",
    "organisationEmail" => "info@better.org.uk",
    "platformName" => "AcmeBooker",
    "platformUrl" => "https://acmebooker.example.com/",
    "platformSoftwareVersion" => "2.0",
    "backgroundImageUrl" => "https://data.better.org.uk/images/bg.jpg",
    "dateFirstPublished" => "2019-10-28",
);
```

And `$feedTypes` could be defined as:
```php
use OpenActive\DatasetSiteTemplate\FeedType;

$feedTypes = array(
    FeedType::FACILITY_USE,
    FeedType::SCHEDULED_SESSION,
    FeedType::SESSION_SERIES,
    FeedType::SLOT,
);
```

### API

#### `renderSimpleDatasetSite($settings, $supportedFeedTypes)`

Returns a string corresponding to the compiled HTML, based on the `datasetsite.mustache`, the provided `$settings`, and `$supportedFeedTypes`.

`$settings` must contain the following keys:

##### Settings

| Key                                | Type       | Description |
| ---------------------------------- | ---------- | ----------- |
| `openDataFeedBaseUrl`              | `string`   | The the base URL for the open data feeds |
| `datasetSiteUrl`                   | `string`   | The URL where this dataset site is displayed (the page's own URL) |
| `datasetDiscussionUrl`             | `string`   | The GitHub issues page for the dataset |
| `datasetDocumentationUrl`          | `string`   | Any documentation specific to the dataset. Defaults to https://developer.openactive.io/ if not provided, which should be used if no documentation is available. |
| `datasetLanguages`                 | `string[]` | The languages available in the dataset, following the IETF BCP 47 standard. Defaults to `array("en-GB")`. |
| `organisationName`                 | `string`   | The publishing organisation's name |
| `organisationUrl`                  | `string`   | The publishing organisation's URL |
| `organisationLegalEntity`          | `string`   | The legal name of the publishing organisation of this dataset |
| `organisationPlainTextDescription` | `string`   | A plain text description of this organisation |
| `organisationLogoUrl`              | `string`   | An image URL of the publishing organisation's logo, ideally in PNG format |
| `organisationEmail`                | `string`   | The contact email of the publishing organisation of this dataset |
| `platformName`                     | `string`   | The software platform's name. Only set this if different from the publishing organisation, otherwise leave as null to exclude platform metadata. |
| `platformUrl`                      | `string`   | The software platform's website |
| `platformSoftwareVersion`          | `string`   | The software platform's software version |
| `backgroundImageUrl`               | `string`   | The background image to show on the Dataset Site page |
| `dateFirstPublished`               | `string`   | The date the dataset was first published |

And `$supportedFeedTypes` must be an `array` of `FeedType` constants, which auto-generates the metadata associated which each feed using best-practice values. See [available types](#feedtype)

#### `renderSimpleDatasetSiteFromDataDownloads($settings, $dataDownloads, $dataFeedDescriptions)`

Returns a string corresponding to the compiled HTML, based on the `datasetsite.mustache`, the provided [`$settings`](#settings), `$dataDownloads` and `$dataFeedDescriptions`.

The `$dataDownloads` argument must be an `array` of `\OpenActive\Models\OA\DataDownload` objects, which each describe an available open data feed.

The `$dataFeedDescriptions` must be an array of strings that describe the dataset, e.g:
```php
$dataFeedDescriptions = array(
    "Sessions",
    "Facilities"
);
```

#### `renderDatasetSite($dataset)`

Returns a string corresponding to the compiled HTML, based on the `datasetsite.mustache`, and the provided `$dataset`.

The `$dataset` argument must be an object of type `\OpenActive\Models\OA\Dataset`, and must contain the properties required to render the dataset site.

#### `FeedType`

A class containing the supported distribution types:

| Constant                  | Value                   |
| ------------------------- | ----------------------- |
| `COURSE`                  | `Course`                |
| `COURSE_INSTANCE`         | `CourseInstance`        |
| `EVENT`                   | `Event`                 |
| `EVENT_SERIES`            | `EventSeries`           |
| `FACILITY_USE`            | `FacilityUse`           |
| `HEADLINE_EVENT`          | `HeadlineEvent`         |
| `INDIVIDUAL_FACILITY_USE` | `IndividualFacilityUse` |
| `SCHEDULED_SESSION`       | `ScheduledSession`      |
| `SESSION_SERIES`          | `SessionSeries`         |
| `SLOT`                    | `Slot`                  |

You can use any of the above like this:
```php
use OpenActive\DatasetSiteTemplate\FeedType;

echo FeedType::COURSE_INSTANCE;
```

Which will output:
```
CourseInstance
```

## Contribution

### Installation
```bash
git clone https://github.com/openactive/dataset-site-template-php.git
cd dataset-site-template-php
composer install
```

### Example
From a web server capable of interpreting and compiling PHP, navigate to the `/openactive` folder.

From there you should be able to see the template populated with the JSON-LD data.

The default mustache template (`datasetsite.mustache`) is included under the `src` folder.

In `index.php` you can find an example of the associative array that's going to get parsed by `TemplateRenderer`.

### Running Tests
PHPUnit 4.8 is used to run tests.

To run the whole suite:
```bash
./vendor/bin/phpunit
```

If you want to run the whole suite in verbose mode:
```bash
./vendor/bin/phpunit --verbose
```

You can also run a section of the suite by specifying the class's relative path on which you want to perform tests:
```bash
./vendor/bin/phpunit --verbose tests/Unit/TemplateRendererTest.php
```

For additional information on the commands available for PHPUnit,
consult [their documentation](https://phpunit.de/manual/4.8/en/installation.html)
