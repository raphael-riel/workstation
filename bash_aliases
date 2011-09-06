alias g="git status"
alias l="ll"

alias subl="~/work/software/sublime/sublime_text"

# enable color support of ls and also add handy aliases
eval `dircolors -b`
alias ls='ls --color=auto'

# some more ls aliases
alias ll='ls -lhX'
alias la='ls -A'
alias lla='ll -A'
alias ldir='ls -lhA |grep ^d'
alias lfiles='ls -lhA |grep ^-'

# To see something coming into ls output: lss
alias lss='ls -lrt | grep $1'

# To check a process is running in a box with a heavy load: pss
alias pss='ps -ef | grep $1'
