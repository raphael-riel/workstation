#!/bin/bash
eval `ssh-agent`;
trap "{ /bin/kill -TERM $SSH_AGENT_PID ; }" EXIT
ssh-add