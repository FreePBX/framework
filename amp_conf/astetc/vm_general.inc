; 1st listed format gets emailed
format=wav49|wav

attach=yes
pbxskip=yes              	; Don't put [PBX]: in the subject line
serveremail=vm@asterisk		; Who the e-mail notification should appear to come from
fromstring=Voicemail System ; Real name of email sender
maxmessage=180          	; max length of vm message
minmessage=3				; Minimum length of a voicemail message in seconds
maxsilence=5            	; Wait for 5 silent seconds and end the voicemail
silencethreshold=128     	; What do we consider to be silence
skipms=3000					; How many miliseconds to skip forward/back when rew/ff in message playback
review=yes					; Allow sender to review/rerecord their message before saving it
operator=yes				; Allow caller to press 0
