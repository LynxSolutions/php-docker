#!/usr/bin/env bash
set -Eeuo pipefail

[ -f versions.json ] # run "versions.php" first

jqt='.jq-template.awk'
if [ ! -f "${jqt}" ]; then
    echo "Downloading jq-template.awk ..."
    # https://github.com/docker-library/bashbrew/blob/master/scripts/jq-template.awk
	wget -qO "$jqt" 'https://github.com/docker-library/bashbrew/raw/9f6a35772ac863a0241f147c820354e4008edf38/scripts/jq-template.awk'
fi

if [ "$#" -eq 0 ]; then
	versions="$(jq -r 'keys | map(@sh) | join(" ")' versions.json)"
	eval "set -- $versions"
fi

generated_warning() {
	cat <<-EOH
		#
		# NOTE: THIS DOCKERFILE IS GENERATED VIA "apply-templates.sh"
		#
		# PLEASE DO NOT EDIT IT DIRECTLY.
		#

	EOH
}

for version; do
    export version

    rm -rf "$version"

    if jq -e '.[env.version] | not' versions.json > /dev/null; then
        echo "deleting $version ..."
        continue
    fi

    variants="$(jq -r '.[env.version].variants | map(@sh) | join(" ")' versions.json)"
    latest_version="$(jq -r '.[env.version].version' versions.json)"
    eval "variants=( $variants )"

    for dir in "${variants[@]}"; do
        if [[ $dir == *-*-* ]]; then
            variant="${dir%-*}"
            subvariant="${dir##*-}"
        else
            variant="$dir"
            subvariant=""
        fi
        export variant subvariant

        from="php:${latest_version}-${variant}"
        export from

        mkdir -p "$version/$dir"
        cp -r conf.d "$version/$dir/"

        echo "processing: ${version}/$dir ..."

        {
            generated_warning
            gawk -f "$jqt" 'Dockerfile.template'
        } > "$version/$dir/Dockerfile"
    done

done
