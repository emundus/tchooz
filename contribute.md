# Contribution guide

## Gitflow
### Branches
- **master** : This branch is the main branch. This contains the latest stable version of the application. It is protected and can only be updated by a merge request.
- **hotfix** : This branch is used to prepare the next patch. This is created from the master branch and merged into the master branch. Only minor fixes (style, translations, minor impacts) are allowed to be committed directly in this branch. It's recommended to create a new branch from this one to fix a bug.
- **patch/xxx** : These branches are used to fix a bug. They are created from the master branch and merged into the hotfix branch.
- **release** : This branch is used to prepare the next release. This is created from the dev branch and merged into the master branch.
- **dev** : This branch is used to test new features. This is created from the master branch and merged into the release branch. It's recommended to create a new branch from this one to develop a new feature.
- **feature/xxx** : These branches are used to develop a new feature. They are created from the dev branch and merged into the dev branch.
- **platform/xxx** : These branches are used to deploy a new feature in a specific environment. They are created from the dev branch.

### Simple gitflow

```mermaid
%%{init: { 'logLevel': 'debug', 'theme': 'base', 'gitGraph': {'showBranches': true, 'showCommitLabel':false,'mainBranchName': 'master'}} }%%
gitGraph
    commit id: "Initial commit" tag: "v1.0.0"
    branch hotfix order: 1
    branch release order: 3
    branch dev order: 4
    branch feature/xxx order: 5
    commit id: "Add workflow builder"
    checkout hotfix
    branch patch/xxx order: 2
    commit id: "Fix security issue"
    checkout hotfix
    merge patch/xxx id: "Security patch"
    checkout master
    merge hotfix id: "Release 1.0.1" tag: "v1.0.1"
    checkout feature/xxx
    commit id: "Add new feature"
    checkout dev
    merge feature/xxx
    checkout release
    merge dev
    checkout master
    merge release id: "Release 1.1.0" tag: "v1.1.0"
```    

### Complete gitflow
![Gitflow][gitflow]
- **cherry-pick** : This command allows you to apply a commit from another branch. It is useful when you need to apply a security fix from a hotfix branch to a platform branch.

### Pipelines
#### Merge request
##### All branches
```mermaid
%%{init: { 'logLevel': 'debug', 'theme': 'forest', 'gitGraph': {'showBranches': false, 'showCommitLabel':true,'mainBranchName': 'master'}} }%%
gitGraph LR:
    commit id: "Merge request opened"
    commit id: "dependency-check"
    commit id: "unittest-front"
    commit id: "unittest-back"
    commit id: "integration-test" type: HIGHLIGHT
```
- **dependency-check** : Check if there is any security issue in the dependencies (npm audit, composer audit)
- **unittest-front** : Run the unit tests for the front part (VueJS)
- **unittest-back** : Run the unit tests for the back part (PHP)
- **integration-test** : Run the integration tests (Playwright). This is optional but can be useful to check if the application is working as expected

##### Master
```mermaid
%%{init: { 'logLevel': 'debug', 'theme': 'forest', 'gitGraph': {'showBranches': false, 'showCommitLabel':true,'mainBranchName': 'master'}} }%%
gitGraph
    commit id: "Merge request opened"
    commit id: "version-check"
    commit id: "commit-prefix-check"
    commit id: "dependency-check"
    commit id: "unittest-front"
    commit id: "unittest-back"
    commit id: "integration-test" type: HIGHLIGHT
```
- **version-check** : Check if the version in the xml file (administrator/components/com_emundus/emundus.xml) was updated since the last release
- **commit-prefix-check** : Check if at least one commit has a prefix (feat, fix, refactor, style, test, chore)
- **dependency-check** : Check if there is any security issue in the dependencies (npm audit, composer audit)
- **unittest-front** : Run the unit tests for the front part (VueJS)
- **unittest-back** : Run the unit tests for the back part (PHP)
- **integration-test** : Run the integration tests (Playwright). This is optional but can be useful to check if the application is working as expected

#### Commit
##### All branches
```mermaid
%%{init: { 'logLevel': 'debug', 'theme': 'forest', 'gitGraph': {'showBranches': false, 'showCommitLabel':true,'mainBranchName': 'master'}} }%%
gitGraph
    commit id: "Push new commit"
    commit id: "secret-detection"
    
```
- **secret-detection** : Check if there is any secret in the code
- **semgrep-sast** : This is a Static Application Security Testing (SAST) tool that analyzes source code for security vulnerabilities.

##### Releases
```mermaid
%%{init: { 'logLevel': 'debug', 'theme': 'forest', 'gitGraph': {'showBranches': false, 'showCommitLabel':true,'mainBranchName': 'master'}} }%%
gitGraph
    commit id: "Branch merged"
    commit id: "deployer-auto"
    
```
- **deployer-auto** : Deploy the new release in some environments (staging or testing)

##### Master
```mermaid
%%{init: { 'logLevel': 'debug', 'theme': 'forest', 'gitGraph': {'showBranches': false, 'showCommitLabel':true,'mainBranchName': 'master'}} }%%
gitGraph
    commit id: "Branch merged"
    commit id: "semantic-release"
    commit id: "sync-confluence-documentation"
    commit id: "deployer-auto"
    commit id: "deployer-manuel" type: HIGHLIGHT
```
- **semantic-release** : Create a new version in the master branch with a new tag (X.X.X) and generate release notes with the names of commits since the last version
- **sync-confluence-documentation** : Update Confluence release notes
- **deployer-auto** : Deploy the new release in some environments
- **deployer-manuel** : Allows manual deployment of the new version in other environments

<!-- RELEASES -->

## Release management
We use the Semantic Versioning convention for version numbers. For more information, please visit [semver.org](https://semver.org/).

Each release is therefore named like this: `<major> ‘.’ <minor> ‘.’ <patch>` (example 1.20.4).

`<major>`: The major number indicates the major version of the software, which means that there have been major changes that are potentially incompatible with previous versions.

`<minor>`: The minor number indicates the minor version of the software, meaning that there have been minor changes, such as bug fixes, performance improvements or new features, but which are compatible with previous versions.

`<patch>`: The patch number indicates the patch version of the software. This means that bugs or vulnerabilities have been fixed.

The release number is incremented by checking the names of all the commits included in the history of the merge request or squash commit where applicable.

## Naming convention
> [!WARNING]  
> The prefixes must be used at the beginning of the name of your commits, and the space after the : is essential!

### Are you releasing a major version of the product?
At least one commit must be present in the commit history of your merge request with one of these prefixes:
- `BREAKING:`
- `BREAKING CHANGE:`
- `BREAKING CHANGES:`

> This will trigger the creation of a major release (example 1.0.0 → 2.0.0) when merging to the main branch.

### Are you releasing a feature?
At least one commit must be present in the commit history of your merge request with one of these prefixes:
- `minor:`
- `feat:`
- `feature:`
> This will trigger the creation of a major release (example 1.0.0 → 1.1.0) when merging to the main branch.

### Are you committing a patch or hotfix?
At least one commit must be present in the commit history of your merge request with one of these prefixes:
- `patch:`
- `hotfix:`
- `security:`
- `fix:`
- `style:`
- `refactor:`
- `perf:`
> This will trigger the creation of a major release (example 1.0.0 → 1.0.1) when merging to the main branch.

All the prefixes below will be taken into account in the release creation process when the main branch is merged.

[gitflow]: images/gitflow.png