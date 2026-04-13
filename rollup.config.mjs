import { nodeResolve } from '@rollup/plugin-node-resolve';
import commonjs from '@rollup/plugin-commonjs';

export default [
	{
		input: 'assets/acf-drag-handle.js',
		output: {
			file: 'assets/dist/acf-drag-handle.dist.js',
			format: 'iife',
			sourcemap: true,
			globals: {
				wp: 'wp',
				acf: 'acf'
			}
		},
		external: ['wp', 'acf'],
		plugins: [
			nodeResolve(),
			commonjs(),
		]
	},
];
