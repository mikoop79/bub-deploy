#!/bin/bash
TYPE=$1
MESSAGE=$2;

select_deployment(){

    if [ "$TYPE" == "1" ]; then
    	ENV="ADMIN"
		envoy run deploy --server=bubwise.prod --message=message:"$MESSAGE"
    fi;

    if [ "$TYPE" == "2" ]; then
    	ENV="WEB APP"
		envoy run deploy-build --server=bubwise-vue.prod --message=message:"$MESSAGE"
    fi;

}

select_deployment "$TYPE" "$MESSAGE"

echo "Finished Deploying $ENV";
