#! /bin/bash

tools/oktmo_prep.bash $1
tools/import_oktmo.bash
tools/deep.bash oktmo
tools/merge_code.bash oktmo
