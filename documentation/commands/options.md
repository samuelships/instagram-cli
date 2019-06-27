# Options

## General Options
These options can be used across multiple commands.

| Option     | Description     |
| ---        | ---             |
| `--white_list` | Inline white list of usernames you want to exclude in the operation e.g ``` --white_list= "thisisbillgates,elon_musk"```|
| `--white_list_from_file` | White list from a file, the file content is exactly the same format as that of the `--white_lst` e.g ``` --white_list_from_file= "wl.txt```|

## Notifications Options
These options can be used with just the notifications command.

| Option     | Description     |
| ---        | ---             |
| `--log_to_file` | It logs incoming notifications to the json file e.g ``` --log_to_file= true```|