<?php return array (
  'App\\Providers\\EventServiceProvider' => 
  array (
    'App\\Events\\InvitationCreated' => 
    array (
      0 => 'App\\Listeners\\SendInvitationEmailListener',
    ),
    'App\\Events\\UserInvited' => 
    array (
      0 => 'App\\Listeners\\SendUserInvitationListener',
    ),
  ),
  'Illuminate\\Foundation\\Support\\Providers\\EventServiceProvider' => 
  array (
    'App\\Events\\InvitationCreated' => 
    array (
      0 => 'App\\Listeners\\SendInvitationEmailListener@handle',
    ),
    'App\\Events\\UserInvited' => 
    array (
      0 => 'App\\Listeners\\SendUserInvitationListener@handle',
    ),
  ),
);