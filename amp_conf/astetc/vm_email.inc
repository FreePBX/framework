; To be able to localize text in the email by using utf-8 characters you can remove the remark so that the charset will be utf-8
; charset=utf-8
; To change the email subject uncomment the next line and add whatever text you want.
; emailsubject=New voicemail from ${VM_CALLERID} arrived
; Change the email body, variables: VM_NAME, VM_DUR, VM_MSGNUM, VM_MAILBOX, VM_CALLERID, VM_DATE
emailbody=${VM_NAME},\n\nThere is a new voicemail in mailbox ${VM_MAILBOX}:\n\n\tFrom:\t${VM_CALLERID}\n\tLength:\t${VM_DUR} seconds\n\tDate:\t${VM_DATE}\n\nDial *98 to access your voicemail by phone.\nVisit http://AMPWEBADDRESS/recordings/index.php to check your voicemail with a web browser.\n


