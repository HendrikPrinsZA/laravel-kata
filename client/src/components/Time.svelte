<script>
import { time } from '../stores/time';

const formatter = new Intl.DateTimeFormat('en', {
    hour12: false,
    hour: 'numeric',
    minute: '2-digit',
    second: '2-digit'
});
</script>
  
{formatter.format($time)}
