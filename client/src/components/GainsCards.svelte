<script>
    import fetchStore from '../stores/fetch'

    const url = 'http://localhost/api/gains'
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
        </h3>

        <table width="100%">
            <thead>
                <th>Challenge::method</th>
                <th>Result</th>
            </thead>
            <tbody>
                {#each $data.data as record}
                <tr>
                    <td>
                        <a href="/laravel-kata/gains/{record.gains.class}/{record.gains.method}">
                            {record.gains.class}::{record.gains.method}()
                        </a>
                    </td>
                    <td>
                        {#if record.gains.stats.record.gains_success}
                            <span style="color:green">{Math.round(record.gains.stats.record.gains_perc, 2)}%</span>
                        {:else}
                            <span style="color:red">{Math.round(record.gains.stats.record.gains_perc, 2)}%</span>
                        {/if}
                    </td>
                </tr>
                {/each}
            </tbody>
        </table>
    {/if}
</main>

