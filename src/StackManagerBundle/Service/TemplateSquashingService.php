<?php

/*
 * This file is part of the Stack Manager package.
 *
 * © Royal Opera House Covent Garden Foundation <website@roh.org.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ROH\Bundle\StackManagerBundle\Service;

use Guzzle\Service\Builder\ServiceBuilder as AwsClient;
use PHPUnit_Framework_Assert;
use ROH\Bundle\StackManagerBundle\Model\Template;
use Symfony\Component\Serializer;
use stdClass;

/**
 * Service to convert a template to a JSON file and upload it to S3, ready for
 * passing to the CloudFormation API.  Sub-stack template bodies within the
 * template will be uploaded as separate files and converted to a "TemplateURL"
 * property.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class TemplateSquashingService
{
    /**
     * Hash algorithm to use to generate the key of objects templates uploaded
     * to S3.  It is safe to change this at any time.
     */
    const OBJECT_KEY_HASH_ALGORITHM = 'sha256';

    /**
     * Resource type name of sub-stacks in a CloudFormation template.
     */
    const SUB_STACK_RESOURCE_TYPE = 'AWS::CloudFormation::Stack';

    /**
     * @param AwsClient $awsClient AWS API client
     * @param string $s3BucketName Name of S3 bucket to upload templates to.
     */
    public function __construct(AwsClient $awsClient, $s3BucketName)
    {
        $this->s3 = $awsClient->get('S3');
        $this->s3BucketName = $s3BucketName;
    }

    /**
     * Upload the template to S3 (squashing any sub-stack templates) and return
     * the URL.
     *
     * @param Template $template Template to squash and upload.
     * @return string URL of uploaded template.
     */
    public function getSquashedTemplateURL(Template $template)
    {
        $body = clone $template->getBody();
        $this->squashTemplate($body);

        return $this->uploadTemplate($body);
    }

    /**
     * Recursively convert the deepest sub-stack resources from having a
     * TemplateBody to a TemplateURL.
     *
     * @param stdClass $template Template to squash.
     */
    public function squashTemplate(stdClass $template)
    {
        if (!isset($template->Resources)) {
            return;
        }

        foreach ($template->Resources as $name => $data) {
            if ($data->Type !== self::SUB_STACK_RESOURCE_TYPE) {
                continue;
            }

            if (!isset($data->Properties)) {
                continue;
            }

            if (!isset($data->Properties->TemplateBody)) {
                continue;
            }

            $this->squashTemplate($data->Properties->TemplateBody);

            $data->Properties->TemplateURL = $this->uploadTemplate(
                $data->Properties->TemplateBody
            );
            unset($data->Properties->TemplateBody);
        }
    }

    /**
     * Upload the template object to S3, validate it and return the templates
     * URL.  The object key is a hash of the template body, so will be constant
     * providing the template doesn't change.
     *
     * @param stdClass $template Template object to upload
     * @return string URL of template in S3.
     */
    public function uploadTemplate(stdClass $template)
    {
        $json = (new Serializer\Encoder\JsonEncode)->encode(
            $template,
            Serializer\Encoder\JsonEncoder::FORMAT,
            ['json_encode_options' => Template::JSON_OPTIONS]
        );

        // Always append a new line to the JSON to improve output on the console.
        $json .= "\n";

        PHPUnit_Framework_Assert::assertLessThanOrEqual(
            307200, strlen($json),
            'Template body encoded as JSON must contain no more than 307,200 characters'
        );

        $file = hash(self::OBJECT_KEY_HASH_ALGORITHM, $json).'.json';

        $response = $this->s3->PutObject([
            'Bucket' => $this->s3BucketName,
            'Key' => $file,
            'Body' => $json,
            'ACL' => 'public-read',
        ]);

        return $response['ObjectURL'];
    }
}
