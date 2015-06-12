# Stack Manager

Stack Manager is a tool for managing AWS CloudFormation stacks using Twig templates.

The basic CloudFormation language is useful for specifying your infrastructure, but being defined in JSON makes it limited to pure configuration with complex workarounds to specify more advanced features.  Enhancing your templates with Twig using the Stack Manager enables many advanced options:

- A Turing-complete templating language, allowing you to use conditionals and perform calculations
- Macros to reduce duplication
- Previewing stacks before creation and changes before updating a stack
- Scriptable launch and deletion of stacks

## Requirements

Stack Manager requires PHP 5.6.0 or above.

## Installation

Clone the Stack Manager using Git, and install dependencies with [Composer](https://getcomposer.org/):

```bash
git clone https://github.com/royaloperahouse/stack-manager
cd stack-manager
composer install
```

Create a "parameters.ini" file based on "parameters.ini.dist" in the "app/config" directory, four parameters must be set:

- **aws_region:** Region CloudFormation templates will be uploaded to S3 in and stacks will be created in.
- **aws_access_key_id:** AWS access key id used to upload templates to S3 and create stacks.  This IAM user requires all permissions that your CloudFormation template intends to use to create resources.
- **aws_secret_key:** Matching secret key for the access key.
- **aws_template_bucket:** Name of the S3 bucket to upload templates to.  Templates will be uploaded with their name being the hash of their contents and suffixed with ".json".

## Concepts

### Template

Templates are defined using Twig and JSON in the "app/Resources/views" directory, these should create a template ready to be passed to the CloudFormation API.  These templates will be interpreted as JSON by the application and pretty-printed before uploading to an S3 bucket.

The Stack Manager exposes one customisation to the standard CloudFormation language.  If you define the "TemplateBody" property of an "AWS::CloudFormation::Stack" as an object (rather than a string) this will be uploaded to an S3 bucket as a separate template and replaced with a "TemplateURL" property.  This allows you to easily make use of sub-stacks to segment your resources without running in to any limitations of the CloudFormation API.

Each template (not including sub-stack templates) must not exceed 307,200 characters in length.

Stacks are tagged with the name of the template used, so when subsequently updating a stack the application automatically knows the template to use.

### Environment

When creating a stack, you must specify an environment - this selects a set of parameters used when creating the stack (see below).  The stack will be tagged with the name of the environment, and it is fixed for the lifetime of the stack.

### Scaling profile

You may choose a scaling profile when creating or updating a stack, the intention of this is to change the set of parameters used after the stack has been created - for example, you may have a scaling profile that sets the desired capacity of an auto scaling group.

If the scaling profile is not set, the default scaling profile is used.  The scaling profile is not remembered between commands, so if a non-default profile has been selected it must subsequently be specified unless you want to revert to the default.

### Parameters

Configuration for the parameters used when creating a stack is in YAML files kept in the "app/Resources/config" directory.  Configuration matches up with a particular template name, and can be spread over one or more configuration files (these are subsequently merged together).

There are three types of parameters that can be defined for each template:

1. **defaults:** Default parameters used by all stacks of that template, unless overriden.
2. **environments:** Environment specific parameters, overrides and default parameters.  At least one environment must be specified.
3. **scaling-profiles:** Scaling profile specific parameters, overrides environmnt specific and default parameters.  The "default" scaling profile must be specified.

See the sample config file for an example of how to set this.

## Usage

### Creating a stack

You can view a stack before it has been created using the command:

    app/console stack-manager:preview-stack Template Environment [--name=...] [--scaling-profile=...]

This will show the template, parameters and tags of the stack that will be created.

It can be created by running:

    app/console stack-manager:create-stack Template Environment [--name=...] [--scaling-profile=...]

If a name is not specified, a name is automatically generated from the template name, environment and the current year and week number.

### Updating a stack

The changes to the template and parameters that will be made when updating a stack can be seen using the command:

    app/console stack-manager:compare-stack Name [--scaling-profile=...]

If you are happy with the changes, they can be applied by running:

    app/console stack-manager:update-stack Name [--scaling-profile=...]

Remember, if the scaling profile is not specified the stack will revert to the default scaling profile.

### Deleting a stack

When deleting a stack, you must always pass the "--really" parameter:

    app/console stack-manager:delete-stack Name --really

## Temporal scaling

The Temporal Scaling module allows you to automatically update stacks based on events in a calendar feed.  While AWS comes with a range of tools to scale resources on a schedule, generally each product has its own way of describing the changes (which itself needs some manual management).  Additionally, some services lack the ability to scale on a schedule entirely.  Temporal Scaling expands scheduled scaling to any resource that can be described using CloudFormation templates, providing a consistent interface to all of them.

### Set up

At the moment, only Google Calendar feeds are supported.

- Using the [Google Developers Console](https://console.developers.google.com/project) generate an API key for a project that has access to the Google Calendar API enabled.  Add these and an application name (just an arbitrary string) to your parameters.ini as "google_api_application_name" and "google_api_developer_key".

- Create a calendar in Google Calendar.  Within this calendar, the summary of events should correspond to a scaling profile defined in your stack template configuration.

- Populate the "roh_temporal_scaling.calendar_sources" with an array of stack names to calendar ids.

- Run the command to action any scheduled scaling events:

    app/console temporal-scaling:perform-scaling

## About

Stack Manager was created by the [Royal Opera House](http://www.roh.org.uk/digital) for internal use and is made available for the benefit of the community under the [MIT License](LICENSE).

Pull requests are welcomed and will be responded to promptly.
