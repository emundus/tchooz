#!/bin/bash

# Display help
Help()
{
   echo "file-search-in-gitlab-merge-request searches for a specific file in the list of modified files included in the changes of a Gitlab merge request."
   echo
   echo "Usage: file-search-in-gitlab-merge-request.sh [options] [-h] [args...] <GITLAB_URL> <GITLAB_TOKEN> <PROJECT_ID> <MERGE_REQUEST_IID> <FILE>"
   echo
   echo "   -h                         Print this Help."
   echo "   \$1 <GITLAB_URL>            Your Gitlab URL, eg. https://gitlab.com"
   echo "   \$2 <GITLAB_TOKEN>          Token access to your Gitlab project (needs only read-only access to the Gitlab API)"
   echo "   \$3 <PROJECT_ID>            Your Gitlab project ID, eg. 60"
   echo "   \$4 <MERGE_REQUEST_IID>     Your Gitlab Merge Request IID, eg. 23"
   echo "   \$5 <FILE>                  File searched in the merge request, eg. administrator/components/com_emundus/emundus.xml"
   echo 
   echo "Note: all arguments are required !"
}

while getopts ":h" option; do
   case $option in
      h)
         Help
         exit;
   esac
done

if [ "$1" == '' ]; then
    Help
    exit;
fi

# Get parameters
GITLAB_URL=$1
GITLAB_TOKEN=$2
PROJECT_ID=$3
MERGE_REQUEST_IID=$4
FILE=$5

# Get the list of modified files in the merge request with the Gitlab API
page=1
changed_files=""
while true; do
    result=$(curl --silent --location --request GET $(echo $GITLAB_URL)'/api/v4/projects/'$(echo $PROJECT_ID)'/merge_requests/'$(echo $MERGE_REQUEST_IID)'/diffs?page='$(echo $page)'' --header 'PRIVATE-TOKEN: '$(echo $GITLAB_TOKEN))

    # Check if the API returns an empty array (end of the list of modified files)
    if [[ $(echo "$result" | jq '. | length') -eq 0 ]]; then
        break
    fi

    # Extract the list of modified files
    new_files=$(echo "$result" | jq -r '.[].new_path')

    # Concatenate new files with the previous list of files
    changed_files="${changed_files}${new_files}"$'\n'

    # Set the page number to the next page
    page=$((page + 1))

done

# Check that the file is present in the list of modified files and alerts with a shell error code if missing
if [[ $(echo $changed_files | grep "$FILE") ]]; then
    echo "List of files modified in your merge request:"
    echo "$changed_files
    "
    echo "Well done, you didn't forget to update the version field in the $FILE file !"
    exit 0
else
    echo "List of files modified in your merge request:"
    echo "$changed_files
    "
    echo "ERROR: Please update the XML version field in the $FILE file before relaunching the pipeline."
    exit 1
fi