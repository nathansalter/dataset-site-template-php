<?php

namespace OpenActive\DatasetSiteTemplate;

use Mustache_Engine;
use OpenActive\DatasetSiteTemplate\Meta;
use OpenActive\Exceptions\InvalidArgumentException;
use OpenActive\Helpers\JsonLd;
use OpenActive\Helpers\Str;
use OpenActive\Models\OA\Organization;
use OpenActive\Models\OA\ImageObject;
use OpenActive\Models\SchemaOrg\DataDownload;
use OpenActive\Models\SchemaOrg\Dataset;

/**
 *
 */
class TemplateRenderer
{
    /**
     * The mustache engine implementation.
     *
     * @var \Mustache_Engine
     */
    protected $mustacheEngine;

    /**
     * Create a new template renderer instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->mustacheEngine = new Mustache_Engine();
    }

    /**
     * Render the template from a given array of data.
     *
     * @param array $data
     * @return string Rendered template
     */
    public function renderSimpleDatasetSite($data)
    {
        // Create distribution list based on flags
        $distribution = array();
        if(
            array_key_exists("includeCourseInstanceFeed", $data) &&
            $data["includeCourseInstanceFeed"]
        ) {
            $distribution[] = new DataDownload([
                "name" => "FacilityUse",
                "additionalType" => "https://openactive.io/FacilityUse",
                "encodingFormat" => Meta::RPDE_MEDIA_TYPE,
                "contentUrl" => $data["openDataBaseUrl"] . "feeds/facility-uses"
            ]);
        }
        if(
            array_key_exists("includeEventFeed", $data) &&
            $data["includeEventFeed"]
        ) {
            $distribution[] = new DataDownload([
                "name" => "Slot",
                "additionalType" => "https://openactive.io/Slot",
                "encodingFormat" => Meta::RPDE_MEDIA_TYPE,
                "contentUrl" => $data["openDataBaseUrl"] . "feeds/slots"
            ]);
        }
        if(
            array_key_exists("includeScheduledSessionFeed", $data) &&
            $data["includeScheduledSessionFeed"]
        ) {
            $distribution[] = new DataDownload([
                "name" => "ScheduledSession",
                "additionalType" => "https://openactive.io/ScheduledSession",
                "encodingFormat" => Meta::RPDE_MEDIA_TYPE,
                "contentUrl" => $data["openDataBaseUrl"] . "feeds/scheduled-sessions"
            ]);
        }
        if(
            array_key_exists("includeSessionSeriesFeed", $data) &&
            $data["includeSessionSeriesFeed"]
        ) {
            $distribution[] = new DataDownload([
                "name" => "SessionSeries",
                "additionalType" => "https://openactive.io/SessionSeries",
                "encodingFormat" => Meta::RPDE_MEDIA_TYPE,
                "contentUrl" => $data["openDataBaseUrl"] . "feeds/session-series"
            ]);
        }

        // TODO: Where does bookingBaseUrl go?

        // Create dataset from data
        $dataset = new Dataset([
            "id" => $data["datasetSiteUrl"],
            "url" => $data["datasetSiteUrl"],
            "name" => $data["name"],
            "description" => "Near real-time availability and rich ".
                "descriptions relating to the sessions and facilities ".
                "available from ".$data["organisationName"].", published ".
                "using the OpenActive Modelling Specification 2.0.",
            "keywords" => [
                "Sessions",
                "Facilities",
                "Activities",
                "Sports",
                "Physical Activity",
                "OpenActive"
            ],
            "license" => "https://creativecommons.org/licenses/by/4.0/",
            "discussionUrl" => $data["datasetSiteDiscussionUrl"],
            "inLanguage" => "en-GB",
            "schemaVersion" => "https://www.openactive.io/modelling-opportunity-data/2.0/",
            "publisher" => new Organization([
                "name" => $data["organisationName"],
                "legalName" => $data["legalEntity"],
                "description" => $data["plainTextDescription"],
                "email" => $data["email"],
                "url" => $data["organisationUrl"],
                "logo" => new ImageObject([
                    "url" => $data["organisationLogoUrl"]
                ])
            ]),
            "distribution" => $distribution,
            "datePublished" => new \DateTime("now", new \DateTimeZone("UTC")),
        ]);

        // data that does not belong to the Dataset model but needs rendering anyway
        $additionalData = array(
            "backgroundImageUrl" => $data["backgroundImageUrl"],
            "documentationUrl" => $data["documentationUrl"],
            "platformName" => $data["platformName"],
            "platformUrl" => $data["platformUrl"],
        );

        // Render compiled template with JSON-LD data
        return $this->renderDatasetSite($dataset, $additionalData);
    }

    /**
     * Render the template from a given OpenActive dataset model.
     *
     * @param \OpenActive\Models\Dataset $model The OpenActive model.
     * @param array $additionalData Additional data not belonging to the Dataset model.
     * @return string Rendered template
     */
    public function renderDatasetSite($model, $additionalData)
    {
        if($model instanceof OpenActive\Models\SchemaOrg\Dataset) {
            throw new InvalidArgumentException(
                "Invalid argument type. Argument must be an instance of type ".
                "\OpenActive\Models\SchemaOrg\Dataset, ".
                get_class($model)." given."
            );
        }

        // Get template from FS
        $template = file_get_contents(__DIR__."/datasetsite.mustache");

        // Build data from model's getters
        $data = array(
            "backgroundImage" => $additionalData["backgroundImageUrl"],
            "documentation" => $additionalData["documentationUrl"],
            "platformName" => $additionalData["platformName"],
            "platformUrl" => $additionalData["platformUrl"],
            "softwareVersion" => Meta::VERSION,
        );
        $attributeNames = array(
            "description",
            "discussionUrl",
            "license",
            "name",
            "schemaVersion",
            "url",
        );
        foreach ($attributeNames as $attributeName) {
            $getterName = "get" . Str::pascal($attributeName);

            $data[$attributeName] = $model->$getterName();
        }

        // Build datePublished
        $data["datePublished"] = JsonLd::prepareDataForSerialization($model->getDatePublished());

        // Build distribution attribute for mustache template
        // from model's distribution
        $data["distribution"] = array_map(
            function($distributionItem) {
                return array(
                    "contentUrl" => $distributionItem->getContentUrl(),
                    "encodingFormat" => $distributionItem->getEncodingFormat(),
                    "name" => $distributionItem->getName(),
                );
            },
            $model->getDistribution()
        );

        // Build publisher attribute for mustache template
        // From model's publisher
        $modelPublisher = $model->getPublisher();
        $data["publisher"] = array(
            "legalName" => $modelPublisher->getLegalName(),
            "logo" => array(
                "url" => $modelPublisher->getLogo()->getUrl(),
            ),
            "name" => $modelPublisher->getName(),
            "url" => $modelPublisher->getUrl(),
        );

        // JSON-LD is the serialized content
        $data["json"] = Dataset::serialize($model, true);

        // Render compiled template with JSON-LD data
        return $this->mustacheEngine->render($template, $data);
    }
}
