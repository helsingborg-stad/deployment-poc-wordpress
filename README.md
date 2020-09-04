# Deployment POC Wordpress
Wordpress in docker POC with IAC and CI/CD pipeline using AWS.

## Requirements
* AWS CLI
* yarn
* Docker

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