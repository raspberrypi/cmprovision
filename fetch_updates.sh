#!/bin/bash
# to run with cron add the following 
# it will run at 5 past each hour between 08.00 and 18.05 hrs
# 5 8-18 * * * /bin/bash /home/admin/fetch_updates.sh 2>&1 | logger -t fetch_updates
# TODO
# add openport to provisioner

# secrets eg ACC_KEY are sourced from .env file
source .env

IMG_NAME="facentry-image.img.xz"
NEW_IMG_STORAGE_PATH="/var/lib/cmprovision"
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
    echo "az is not installed, please install with the following command;"
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
    echo "Fetching $NEW_IMG_NAME"
    CURRENT_VERSION_DATE=$(sed -E 's/T/-/; s/[":T]//g' < current_version.txt | cut -c 1-15)
    NEW_IMG_NAME=${CURRENT_VERSION_DATE}-raspios-bullseye-arm64-lite.img.xz
    echo "Entering fetch_updated_img function"
    az storage blob download \
    --account-name facentrytest  \
    --container-name images \
    --name facentry-image.img.xz \
    --file "$NEW_IMG_NAME" \
    --account-key "$ACC_KEY"
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
	sudo mv "$NEW_IMG_NAME" "$NEW_IMG_STORAGE_PATH/$NEW_IMG_NAME"
	echo "New image ready for import"
	sudo chown www-data:www-data "$NEW_IMG_STORAGE_PATH/$NEW_IMG_NAME"
    # import the new image to the project and set as active;
    cd /var/lib/cmprovision/
	echo "Importing new image"
        sudo php artisan import:image "$NEW_IMG_NAME"
    fi
}

fetch_current_version_meta
compare_versions
mv "$HOME/$CURRENT_VERSION_FILE" "$HOME/$OLD_VERSION_FILE"
echo "Completed"
# END OF IMG UPDATER