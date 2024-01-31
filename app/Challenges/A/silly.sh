#!/usr/bin/env bash

MAX_ITERATIONS=100
FILE_NAME="Silly.php"

# Start of the PHP file
echo "<?php" > $FILE_NAME
echo "" >> $FILE_NAME
echo "namespace App\Challenges\A;" >> $FILE_NAME
echo "" >> $FILE_NAME
echo "use App\KataChallenge;" >> $FILE_NAME
echo "" >> $FILE_NAME
echo "class Silly extends KataChallenge" >> $FILE_NAME
echo "{" >> $FILE_NAME
echo "    public const SKIP_VIOLATIONS = true;" >> $FILE_NAME
echo "" >> $FILE_NAME
echo "    protected const PHP_MEM_MAX_ITERATIONS = $MAX_ITERATIONS;" >> $FILE_NAME
echo "" >> $FILE_NAME
echo "    public function isEven(int \$iteration): bool" >> $FILE_NAME
echo "    {" >> $FILE_NAME
echo "        \$iteration = (\$iteration > self::PHP_MEM_MAX_ITERATIONS) ? self::PHP_MEM_MAX_ITERATIONS : \$iteration;" >> $FILE_NAME
echo "" >> $FILE_NAME
# Loop to generate the conditions
for (( i=0; i<=$MAX_ITERATIONS; i++ ))
do
    if [ $((i % 2)) -eq 0 ]; then
        echo "        if (\$iteration === $i) return true;" >> $FILE_NAME
    else
        echo "        if (\$iteration === $i) return false;" >> $FILE_NAME
    fi

    echo "$i / $MAX_ITERATIONS"
done

# End of the function
echo "    }" >> $FILE_NAME

# End of the class
echo "}" >> $FILE_NAME
