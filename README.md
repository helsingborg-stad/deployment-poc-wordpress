<!-- SHIELDS -->
[![Contributors][contributors-shield]][contributors-url]
[![Forks][forks-shield]][forks-url]
[![Stargazers][stars-shield]][stars-url]
[![Issues][issues-shield]][issues-url]
[![License][license-shield]][license-url]

<p>
  <a href="https://github.com/helsingborg-stad/dev-guide">
    <img src="images/logo.jpg" alt="Logo" width="300">
  </a>
</p>
<h3>Deployment POC Wordpress</h3>
<p>
  Everything you need to be a developer at Helsingborg Stad.
  <br />
  <a href="https://github.com/helsingborg-stad/dev-guide/issues">Report Bug</a>
  ·
  <a href="https://github.com/helsingborg-stad/dev-guide/issues">Request Feature</a>
</p>




## Table of Contents
- [Table of Contents](#table-of-contents)
- [About Deployment POC Wordpress](#about-deployment-poc-wordpress)
  - [Built With](#built-with)
- [Getting Started](#getting-started)
  - [Prerequisites](#prerequisites)
  - [Installation](#installation)
- [Local dev environment](#local-dev-environment)
- [CI/CD Pipeline](#cicd-pipeline)
  - [Github web hooks](#github-web-hooks)
    - [Create git access token](#create-git-access-token)
    - [Add token to AWS Secrets Manager](#add-token-to-aws-secrets-manager)
  - [Cloudformation](#cloudformation)
    - [Wordpress secrets](#wordpress-secrets)
- [Usage](#usage)
- [Deploy](#deploy)
- [Roadmap](#roadmap)
- [Contributing](#contributing)
- [License](#license)
- [Acknowledgements](#acknowledgements)



## About Deployment POC Wordpress

[![Deployment POC Wordpress Screen Shot][product-screenshot]](https://example.com)

Wordpress in docker POC with IAC and CI/CD pipeline using AWS.



### Built With

* []()
* []()
* []()



## Getting Started

To get a local copy up and running follow these simple steps.



### Prerequisites
* AWS CLI
* yarn
* Docker



### Installation

1. Clone the repo
```sh
git clone https://github.com/helsingborg-stad/deployment-poc-wordpress.git
```
## Local dev environment

## CI/CD Pipeline
The CI/CD pipeline is to be created once at the project startup and will be mostly static throughout the project lifetime.  
It uses the AWS CodePipeline service to achive the following.
* Automatically build and push the docker images to AWS ECR container registry.  
* Create a stage and production environment with basically identical setup.  
* Integration test on a stage environment that will stop the deploy on fail.


### Github web hooks
To get CodePipeline to react on merges to master you will need web hooks setup.
If you already have a access token for previous AWS codepipelines you can reuse it in this pipeline and you can skip the below steps.  
In order to get cloudformation to set up the webhook between the pipeline and git you will need an accesss key from the github and provide it to cloudformation.  

#### Create git access token
From [AWS documentation](https://docs.aws.amazon.com/codepipeline/latest/userguide/GitHub-create-personal-token-CLI.html)
  
* In Github click you profile picture and chose `Settings`.
* Select `Developer settings` in the meny and then `Personal access tokens`.
* Generate new token.
* Leave a note with something related e.g. `AWS Codepipeline token`.
* Under `Select scopes`, select `admin:repo_hook` and `repo`.
* Select `Generate token` and save it for later use in `AWS Secrets manager`. 

#### Add token to AWS Secrets Manager
Place the github token in AWS Secrets manager.  
The key name and secret name should match the [Dynamic reference](https://docs.aws.amazon.com/AWSCloudFormation/latest/UserGuide/dynamic-references.html) string in  
`cloudformation/pipeline/cfn-stack.yaml` e.g. `{{resolve:secretsmanager:CodepipelineGithubAccessToken:SecretString:token}}`  
* Go to AWS Secrets Manager and select `Secrets` in the menu.
* Select `Store new Secret`.
* Choose `Other type of secrets`.
* Add the generated token with the key name `token`, then choose `Next`.
* Give the `Secret name` `CodepipelineGithubAccessToken`, leave a tag `Billing` with value `shared` for billing cost management and a description eg. `Git hub access token for Codepipelines`, then choose `Next`.
* Choose `Next` to skip rotation.
* Review and choose `Store`to save the token.


### Cloudformation
The cloudformation template is located in `cloudformation/pipeline/cfn-stack.yml`.  
The parameters used is located in `cloudformation/pipeline/cfn-stack.parameters.json`.  
Edit the parameters to point to you repo.  
  
Create the codepipeline in cloudformation using the below command in your terminal in the repo root directory as working directory.  
Validate your template before creating the stack and fix any errors.  
`aws cloudformation validate-template --template-body file://$PWD/cloudformation/pipeline/cfn-stack.yml`  
  
Create/update and delete.  
`aws cloudformation create-stack --stack-name deployment-poc-wordpress-codepipeline --template-body file://$PWD/cloudformation/pipeline/cfn-stack.yml --capabilities CAPABILITY_IAM`  
`aws cloudformation update-stack --stack-name deployment-poc-wordpress-codepipeline --template-body file://$PWD/cloudformation/pipeline/cfn-stack.yml --capabilities CAPABILITY_IAM`  
`aws cloudformation delete-stack --stack-name deployment-poc-wordpress-codepipeline`  

#### Wordpress secrets
Copy the file cloudformation/secrets/secrets-example.json to cloudformation/secrets/secrets.json and fill all the desired values.

Create stack.
```
aws cloudformation create-stack --stack-name wordpress-secrets --parameters file://$PWD/cloudformation/secrets/secrets.json --template-body file://$PWD/cloudformation/secrets/cfn-stack.yml
```

Update stack.
```
aws cloudformation update-stack --stack-name wordpress-secrets --parameters file://$PWD/cloudformation/secrets/secrets.json --template-body file://$PWD/cloudformation/secrets/cfn-stack.yml
```

Delete stack.
```
aws cloudformation delete-stack --stack-name wordpress-secrets
```

## Usage

Use this space to show useful examples of how a project can be used. Additional screenshots, code examples and demos work well in this space. You may also link to more resources.

_For more examples, please refer to the [Documentation](https://example.com)_



## Deploy

Instructions for deploys.



## Roadmap

See the [open issues][issues-url] for a list of proposed features (and known issues).



## Contributing

Contributions are what make the open source community such an amazing place to be learn, inspire, and create. Any contributions you make are **greatly appreciated**.

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request



## License

Distributed under the [MIT License][license-url].



## Acknowledgements

- [othneildrew Best README Template](https://github.com/othneildrew/Best-README-Template)



<!-- MARKDOWN LINKS & IMAGES -->
<!-- https://www.markdownguide.org/basic-syntax/#reference-style-links -->
[contributors-shield]: https://img.shields.io/github/contributors/helsingborg-stad/deployment-poc-wordpress.svg?style=flat-square
[contributors-url]: https://github.com/helsingborg-stad/deployment-poc-wordpress/graphs/contributors
[forks-shield]: https://img.shields.io/github/forks/helsingborg-stad/deployment-poc-wordpress.svg?style=flat-square
[forks-url]: https://github.com/helsingborg-stad/deployment-poc-wordpress/network/members
[stars-shield]: https://img.shields.io/github/stars/helsingborg-stad/deployment-poc-wordpress.svg?style=flat-square
[stars-url]: https://github.com/helsingborg-stad/deployment-poc-wordpress/stargazers
[issues-shield]: https://img.shields.io/github/issues/helsingborg-stad/deployment-poc-wordpress.svg?style=flat-square
[issues-url]: https://github.com/helsingborg-stad/deployment-poc-wordpress/issues
[license-shield]: https://img.shields.io/github/license/helsingborg-stad/deployment-poc-wordpress.svg?style=flat-square
[license-url]: https://raw.githubusercontent.com/helsingborg-stad/deployment-poc-wordpress/master/LICENSE
[product-screenshot]: images/screenshot.png




