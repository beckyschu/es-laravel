#!/bin/sh

# Flush current rules
iptables --flush

# Set default chains
iptables -P INPUT ACCEPT
iptables -P FORWARD ACCEPT
iptables -P OUTPUT ACCEPT

# Accept related and established input
iptables -A INPUT -m state --state RELATED,ESTABLISHED -j ACCEPT

# Accept ICMP input
iptables -A INPUT -p icmp -j ACCEPT

# Accept local input
iptables -A INPUT -i lo -j ACCEPT

# Accept SSH input
iptables -A INPUT -p tcp -m state --state NEW -m tcp --dport 22 -j ACCEPT

# Accept HTTP input on 80 and 8080
iptables -A INPUT -i eth0 -p tcp -m tcp --dport 80 -m state --state NEW,ESTABLISHED -j ACCEPT
iptables -A INPUT -i eth0 -p tcp -m tcp --dport 8080 -m state --state NEW,ESTABLISHED -j ACCEPT

# Accept web socket connections
iptables -A INPUT -i eth0 -p tcp -m tcp --dport 6001 -m state --state NEW,ESTABLISHED -j ACCEPT

# Accept redis connections
iptables -A INPUT -i eth0 -p tcp -m tcp --dport 6379 -m state --state NEW,ESTABLISHED -j ACCEPT

# Reject remaining input
iptables -A INPUT -j REJECT --reject-with icmp-host-prohibited

# Reject forwarding input
iptables -A FORWARD -j REJECT --reject-with icmp-host-prohibited

# Save current rules permanently
service iptables save

# Restart service
service iptables restart

# Output rules
iptables --list