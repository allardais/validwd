#! /bin/bash

tools/okato_prep.bash $1
tools/import_okato.bash
tools/deep.bash okato
tools/merge_code.bash okato
