```mermaid
%%{init: { 'logLevel': 'debug', 'theme': 'base', 'gitGraph': {'showBranches': true, 'showCommitLabel':false,'mainBranchName': 'master'}} }%% 
gitGraph
        commit id: "Initial commit" tag: "v1.0.0"
        branch hotfix order: 1
        checkout hotfix
        branch release order: 3
        checkout master
        checkout release
        branch dev order: 4
        checkout dev
        branch feature/feature1 order: 5
        checkout feature/feature1
        commit id: "Init workflow builder"
        checkout dev
        branch feature/feature2 order: 6
        commit id: "Init other feature"
        checkout feature/feature1
        commit id: "Add workflow builder"
        checkout dev
        branch platform/demo order: 7
        checkout platform/demo
        merge feature/feature1
        checkout feature/feature2
        commit id: "Add conditionnal builder"
        commit id: "Fix conditionnal builder"
        checkout platform/demo
        merge feature/feature2
        checkout dev
        branch feature/other_feature order: 8
        checkout feature/other_feature
        commit id: "Add other feature"
        checkout hotfix
        branch security/some_security_patches order: 2
        checkout security/some_security_patches
        commit id: "Fix security issue"
        checkout hotfix
        merge security/some_security_patches id: "Security patch"
        branch patch/some_other_bugs order: 2
        checkout patch/some_other_bugs
        commit id: "Fix bug"
        checkout hotfix
        merge patch/some_other_bugs
        checkout master
        merge hotfix id: "Release 1.0.1" tag: "v1.0.1"
        checkout dev
        merge feature/feature2
        merge feature/other_feature
        checkout release
        merge dev
        checkout master
        merge release id: "Release 1.1.0" tag: "v1.1.0"
        checkout feature/feature1
        commit id: "Add new feature"
        checkout platform/demo
        merge feature/feature1
        cherry-pick id:"Security patch" parent:"Fix security issue"
        checkout dev
        merge feature/feature1
        checkout release
        merge dev
        checkout master
        merge release id: "Release 1.2.0" tag: "v1.2.0"
```