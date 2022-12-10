<script>
    import Time from './Time.svelte'
    import fetchStore from '../stores/fetch'

    const url = 'http://localhost/api/gains/KataChallengeSample/calculatePi'
    const [data, loading, error, get] = fetchStore(url)
</script>

<main>
    <h1>Gains Card (<Time load:client/>)</h1>
    {#if $loading}
        Loading: {$loading}
    {:else if $error}
        Error: {$error}
    {:else}
        {#each $data.data as rec}
        <pre>{JSON.stringify(rec, null, 2)}</pre>
        {/each}
    {/if}
</main>

