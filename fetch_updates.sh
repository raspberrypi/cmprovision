#!/bin/bash
# TODO
# add openport to provisioner

# secrets eg ACC_KEY are sourced from .env file
source .env

# IMG_NAME="facentry-image.img.xz"
IMG_NAME="testfile2.txt"
# Fetch current version and compare to file
CURRENT_VERSION_FILE="current_version.txt"
OLD_VERSION_FILE="old_version.txt"

if [ ! -f .env ]; then
    echo "No env file present. Please refer to the .env.example file."
    echo "Exiting..."
    exit
else
    echo "Env read, continuing"
fi

if ! command -v az &> /dev/null; then
    echo "az is not installed, pleae install with the following command;"
    echo "curl -L https://aka.ms/InstallAzureCli | bash"
    exit
else
    echo "az is installed, continuing"
fi

az config set extension.use_dynamic_install=yes_without_prompt

if [ ! -f "$OLD_VERSION_FILE" ]; then
    touch "$OLD_VERSION_FILE"
    echo "First run. $OLD_VERSION_FILE placeholder created"
fi

echo "Comparing."

# Define functions
# Function to fetch current modified datestamp
function fetch_current_version_meta {
    echo "Entering fetch_current_version_meta function"
    az storage blob show \
    --container-name images \
    --account-name facentrytest \
    --account-key "$ACC_KEY" \
    --name "$IMG_NAME" \
    --query 'properties.lastModified' \
    > "$CURRENT_VERSION_FILE"
    echo "Current version meta file created"
}

function fetch_updated_img() {
    echo "Entering fetch_updated_img function"
    az storage azcopy blob download \
    --container images \
    --destination "$IMG_NAME" \
    --account-name facentrytest \
    --source "$IMG_NAME" \
    --account-key "$ACC_KEY"
    mv "$CURRENT_VERSION_FILE" "$OLD_VERSION_FILE" 
}

# Function to compare two version files
function compare_versions {
    echo "Entering compare_versions function"
    local version1="$CURRENT_VERSION_FILE"
    local version2="$OLD_VERSION_FILE"
    if [[ $(cat "$version1") == $(cat "$version2") ]]; then
        echo "Local and remote versions are the same - exiting..."
        exit
    else
        echo "Versions are different - fetching update"
        echo "New version found, downloading"
        fetch_updated_img
        echo "Download complete"
        # Call the PHP file to update cmprovision project
        # passing new file to upload; $IMG_NAME
        # standard manual upload;
        # curl 'http://10.69.110.20/addImage' \
        #   -X 'POST' \
        # our custom endpoint under ap/Console/Commands
        # curl http://localhost/UpdateImage.php --user "name:password"
    fi
}

fetch_current_version_meta
compare_versions

echo "Completed"
# END OF IMG UPDATER
