alias g="git status"
alias l="ll"

alias subl="~/work/software/sublime/sublime_text"

alias grep='grep --color=auto'
alias grepn='grep -n'

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

# Check for a process
alias pss='ps aux | grepn $1'
