#!/bin/bash

# Display help
Help()
{
   echo "commit-prefix-compliance-check searches the list of commits included in the changes of a Gitlab merge request if at least one commit uses a prefix that allows the triggering of a release."
   echo
   echo "Usage: commit-prefix-compliance-check.sh [options] [-h] [args...] <GITLAB_URL> <GITLAB_TOKEN> <PROJECT_ID> <MERGE_REQUEST_IID>"
   echo
   echo "   -h                         Print this Help."
   echo "   \$1 <GITLAB_URL>            Your Gitlab URL, eg. https://gitlab.com"
   echo "   \$2 <GITLAB_TOKEN>          Token access to your Gitlab project (needs only read-only access to the Gitlab API)"
   echo "   \$3 <PROJECT_ID>            Your Gitlab project ID, eg. 60"
   echo "   \$4 <MERGE_REQUEST_IID>     Your Gitlab Merge Request IID, eg. 23"
   echo
   echo "Note: all arguments are required !"
}

# Check for help flag
while getopts ":h" option; do
   case $option in
      h)
         Help
         exit;
   esac
done

# Check for arguments
if [ "$1" == '' ]; then
    Help
    exit;
fi

# Get parameters
GITLAB_URL=$1
GITLAB_TOKEN=$2
PROJECT_ID=$3
MERGE_REQUEST_IID=$4
COMMIT_PREFIXES_TRIGGERING_RELEASE=("cd: " "ci: " "cicd: " "doc: " "feat: " "fix: " "hotfix: " "patch: " "perf: " "ref: " "refactor: " "security: " "style: " "test: " "ui: " "uiux: " "ux: ")


# Get commit names in merge request
git_query=""
page=1
while true
do
  result=$(curl --silent --location --request GET "${GITLAB_URL}/api/v4/projects/${PROJECT_ID}/merge_requests/${MERGE_REQUEST_IID}/commits?per_page=100&page=${page}" --header "PRIVATE-TOKEN: ${GITLAB_TOKEN}")
  count=$(echo "${result}" | jq -r '. | length')
  if [ "$count" -eq 0 ]; then
    break
  fi
  git_query="${git_query}${result}"
  page=$((page+1))
done

# Check that all commits in the merge request respect the naming convention
all_valid=true
for commit in $(echo "${git_query}" | jq -r '.[] | @base64'); do
   message=$(echo "${commit}" | base64 -d | jq -r '.message')

   valid=false
   for prefix in "${COMMIT_PREFIXES_TRIGGERING_RELEASE[@]}"; do
      if [[ "$message" == "$prefix"* ]]; then
         valid=true
         break
      fi
   done

   if [ "$valid" = false ]; then
      echo "ERROR: Commit message '$message' does not respect the naming convention."
      all_valid=false
   fi
done

# If at least one commit is invalid, fail the job
if [ "$all_valid" = false ]; then
   echo "ERROR: All commits must use a valid prefix."
   exit 1
else
   echo "Well done! All commits respect the naming convention."
   exit 0
fi