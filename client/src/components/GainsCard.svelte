<script>
    import fetchStore from '../stores/fetch'
    export let theClass, theMethod;

    const url = `http://localhost/api/gains/${theClass}/${theMethod}`
    const [data, loading, error, get] = fetchStore(url)
</script>

<main>
    {#if $loading}
        Loading...
    {:else if $error}
        Failed to load
    {:else}
        <h3>
            <a href="/laravel-kata/">
                <span>
                    Gains
                </span>
            </a>
            / {theClass} / {theMethod}
        </h3>

        <table width="100%">
            <thead>
                <th></th>
                <th>Before</th>
                <th>Record</th>
                <th>Gains</th>
            </thead>
            <tbody>
                {#each [
                    'outputs_md5',
                    'line_count',
                    'violations_count',
                    'iterations',
                    'duration',
                ] as field}
                <tr>
                    <td>{field}</td>
                    <td>{$data.data[0].gains.stats.before[field]}</td>
                    <td>{$data.data[0].gains.stats.record[field]}</td>
                    <td>{$data.data[0].gains.stats.record[`${field}_gains_perc`]}</td>
                </tr>
                {/each}
            </tbody>
        </table>
    {/if}
</main>

